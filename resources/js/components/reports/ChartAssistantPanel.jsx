import React, { useEffect, useMemo, useState } from 'react';
import api from '../../services/api';
import {
    Sparkles,
    Loader2,
    BrainCircuit,
    Lightbulb,
    ShieldCheck,
    Pill,
    Activity,
    FileText,
    Send,
    RefreshCw,
    MessageSquare,
    Copy,
    Download,
} from 'lucide-react';

export default function ChartAssistantPanel({ residentId, residentName }) {
    const [loading, setLoading] = useState(false);
    const [assistant, setAssistant] = useState(null);
    const [payload, setPayload] = useState(null);
    const [workflow, setWorkflow] = useState(null);
    const [workflowLoading, setWorkflowLoading] = useState(false);
    const [windowDays, setWindowDays] = useState(14);
    const [promptInput, setPromptInput] = useState('Summarize the chart trends and recommend next actions.');
    const [appliedPrompt, setAppliedPrompt] = useState('Summarize the chart trends and recommend next actions.');
    const [error, setError] = useState('');
    const [lastRunAt, setLastRunAt] = useState(null);
    const [conversationId, setConversationId] = useState(null);
    const [conversationMessages, setConversationMessages] = useState([]);
    const [chatInput, setChatInput] = useState('');
    const [chatLoading, setChatLoading] = useState(false);
    const [conversations, setConversations] = useState([]);
    const [conversationsLoading, setConversationsLoading] = useState(false);
    const [copied, setCopied] = useState(false);

    const quickPrompts = [
        'Summarize chart trends and next actions.',
        'Focus on medication adherence risks.',
        'List residents who may need follow-up and why.',
        'Highlight escalation triggers and who to notify.',
        'Write a concise shift handoff summary.',
    ];

    const runAssistant = async (promptToUse = promptInput) => {
        if (!residentId) {
            return;
        }

        setLoading(true);
        setWorkflowLoading(true);
        setError('');

        try {
            const response = await api.get(`/charts/assistant/${residentId}`, {
                params: {
                    days: windowDays,
                    window: `last ${windowDays} days`,
                    prompt: promptToUse,
                },
            });

            const chartPayload = response.data?.payload ?? null;
            const assistantResult = response.data?.assistant ?? null;
            const resolvedPrompt = assistantResult?.prompt || promptToUse;
            setAppliedPrompt(resolvedPrompt);
            setAssistant(assistantResult);
            setPayload(chartPayload);
            setLastRunAt(new Date());

            if (chartPayload) {
                try {
                    const workflowResponse = await api.post(`/charts/assistant/${residentId}/workflow`, {
                        vitals: chartPayload.vitals,
                        medications: chartPayload.medications,
                        behavior_charts: chartPayload.behavior_charts,
                        sleep: chartPayload.sleep,
                        appointments: chartPayload.appointments,
                        faxes: chartPayload.faxes,
                    });
                    setWorkflow(workflowResponse.data?.workflow ?? null);
                } catch {
                    setWorkflow(null);
                }
            }
        } catch {
            setAssistant(null);
            setPayload(null);
            setWorkflow(null);
            setError('Unable to generate assistant insights right now. Please retry.');
        } finally {
            setLoading(false);
            setWorkflowLoading(false);
        }
    };

    const loadConversations = async () => {
        if (!residentId) {
            setConversations([]);
            return;
        }

        setConversationsLoading(true);
        try {
            const response = await api.get('/charts/assistant/conversations', {
                params: { resident_id: residentId },
            });
            setConversations(response.data?.conversations ?? []);
        } catch {
            setConversations([]);
        } finally {
            setConversationsLoading(false);
        }
    };

    const loadConversation = async (id) => {
        if (!id) {
            return;
        }

        setChatLoading(true);
        setError('');

        try {
            const response = await api.get(`/charts/assistant/conversations/${id}`);
            const conversation = response.data?.conversation;
            setConversationId(conversation?.id ?? id);
            setConversationMessages(conversation?.messages ?? []);
        } catch {
            setError('Unable to load selected conversation.');
        } finally {
            setChatLoading(false);
        }
    };

    const startNewConversation = async () => {
        setConversationId(null);
        setConversationMessages([]);
        setChatInput('');
        setError('');
        await ensureConversation();
        await loadConversations();
    };

    const ensureConversation = async () => {
        if (conversationId) {
            return conversationId;
        }

        const response = await api.post('/charts/assistant/conversations', {
            resident_id: residentId,
            title: `${residentName || 'Resident'} chart chat`,
        });

        const id = response.data?.conversation?.id;
        if (id) {
            setConversationId(id);
            setConversationMessages(response.data?.conversation?.messages ?? []);
            setConversations((prev) => {
                const exists = prev.some((item) => item.id === id);
                if (exists) {
                    return prev;
                }
                const created = response.data?.conversation;
                return [
                    {
                        id,
                        title: created?.title || 'Chart review',
                        updated_at: new Date().toISOString(),
                    },
                    ...prev,
                ];
            });
        }

        return id;
    };

    const sendMessage = async () => {
        const message = chatInput.trim();
        if (!message || !residentId) {
            return;
        }

        setChatLoading(true);
        setError('');

        try {
            const id = await ensureConversation();
            if (!id) {
                throw new Error('Conversation was not created.');
            }

            const response = await api.post(`/charts/assistant/conversations/${id}/messages`, {
                message,
            });

            setConversationMessages(response.data?.conversation?.messages ?? []);

            const assistantResult = response.data?.assistant;
            if (assistantResult) {
                setAssistant((prev) => ({ ...(prev ?? {}), ...assistantResult }));
            }

            setChatInput('');
            await loadConversations();
        } catch {
            setError('Unable to send follow-up message. Please try again.');
        } finally {
            setChatLoading(false);
        }
    };

    useEffect(() => {
        setConversationId(null);
        setConversationMessages([]);
        setChatInput('');
        setError('');

        if (!residentId) {
            return;
        }

        runAssistant(promptInput);
        loadConversations();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [residentId, windowDays]);

    const summary = assistant?.summary || 'Select a resident to generate an insight summary.';
    const insights = useMemo(() => assistant?.insights ?? [], [assistant]);
    const recommendations = useMemo(() => assistant?.recommendations ?? [], [assistant]);

    const summaryExportText = useMemo(() => {
        const insightLines = insights.length > 0
            ? insights.map((item) => `- ${item}`).join('\n')
            : '- No insights available';
        const recommendationLines = recommendations.length > 0
            ? recommendations.map((item) => `- ${item}`).join('\n')
            : '- No recommendations available';

        return [
            `Resident: ${residentName || 'Unknown resident'}`,
            `Window: last ${windowDays} days`,
            `Prompt: ${assistant?.prompt || appliedPrompt || ''}`,
            '',
            'Summary',
            summary || 'No summary available',
            '',
            'Insights',
            insightLines,
            '',
            'Recommendations',
            recommendationLines,
        ].join('\n');
    }, [residentName, windowDays, assistant?.prompt, appliedPrompt, summary, insights, recommendations]);

    const displayedPrompt = assistant?.prompt || appliedPrompt;

    const copySummary = async () => {
        try {
            await navigator.clipboard.writeText(summaryExportText);
            setCopied(true);
            setTimeout(() => setCopied(false), 1400);
        } catch {
            setError('Unable to copy summary text.');
        }
    };

    const exportSummary = () => {
        const residentSlug = String(residentName || 'resident').replace(/\s+/g, '_');
        const stamp = new Date().toISOString().slice(0, 10);
        const blob = new Blob([summaryExportText], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `chart_assistant_${residentSlug}_${stamp}.txt`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    const formatConversationTitle = (conversation) => {
        return conversation?.title || `Conversation #${conversation?.id || ''}`;
    };
    const agentResults = useMemo(() => workflow?.agents ?? [], [workflow]);
    const approvalRequired = Boolean(workflow?.approval_required);
    const medicationSummary = useMemo(() => {
        const data = payload?.medications;
        if (!data) return null;
        return `${data.active_count ?? 0} active medication orders · ${data.missed_count ?? 0} missed administration(s)`;
    }, [payload]);
    const vitalHistory = useMemo(() => payload?.vitals?.history ?? [], [payload]);
    const faxMessages = useMemo(() => payload?.faxes?.messages ?? [], [payload]);

    return (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                    <div className="flex items-center gap-2 text-[var(--theme-primary)] font-semibold">
                        <BrainCircuit className="h-5 w-5" />
                        AI chart assistant
                    </div>
                    <h3 className="mt-2 text-lg font-semibold text-slate-900">
                        {residentName ? `Insights for ${residentName}` : 'Resident insights'}
                    </h3>
                    <p className="mt-1 text-sm text-slate-600">
                        Review the latest vitals, sleep, behavior, and appointment trends in one place.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <label className="text-sm text-slate-600">Window</label>
                    <select
                        value={windowDays}
                        onChange={(e) => setWindowDays(Number(e.target.value))}
                        className="border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value={7}>7 days</option>
                        <option value={14}>14 days</option>
                        <option value={30}>30 days</option>
                    </select>
                    <button
                        type="button"
                        onClick={() => runAssistant(promptInput)}
                        disabled={loading}
                        className="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                        style={{
                            backgroundColor: '#ffffff',
                            borderColor: '#cbd5e1',
                            color: '#0f172a',
                            minHeight: '40px',
                            fontSize: '14px',
                            fontWeight: 500,
                        }}
                    >
                        {loading
                            ? <Loader2 className="h-4 w-4 animate-spin" style={{ color: '#0f172a' }} />
                            : <RefreshCw className="h-4 w-4" style={{ color: '#0f172a' }} />}
                        <span style={{ color: '#0f172a' }}>Refresh</span>
                    </button>
                    <button
                        type="button"
                        onClick={copySummary}
                        className="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-sm hover:bg-slate-50"
                        style={{
                            backgroundColor: '#ffffff',
                            borderColor: '#cbd5e1',
                            color: '#0f172a',
                            minHeight: '40px',
                            fontSize: '14px',
                            fontWeight: 500,
                        }}
                    >
                        <Copy className="h-4 w-4" style={{ color: '#0f172a' }} />
                        <span style={{ color: '#0f172a' }}>{copied ? 'Copied' : 'Copy'}</span>
                    </button>
                    <button
                        type="button"
                        onClick={exportSummary}
                        className="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-sm hover:bg-slate-50"
                        style={{
                            backgroundColor: '#ffffff',
                            borderColor: '#cbd5e1',
                            color: '#0f172a',
                            minHeight: '40px',
                            fontSize: '14px',
                            fontWeight: 500,
                        }}
                    >
                        <Download className="h-4 w-4" style={{ color: '#0f172a' }} />
                        <span style={{ color: '#0f172a' }}>Export TXT</span>
                    </button>
                </div>
            </div>

            <div className="mt-4">
                <label className="block text-sm font-medium text-slate-700 mb-2">Prompt</label>
                <textarea
                    value={promptInput}
                    onChange={(e) => setPromptInput(e.target.value)}
                    rows={2}
                    className="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                    placeholder="Ask the assistant to focus on a concern"
                />
                <div className="mt-2 flex flex-wrap gap-2">
                    {quickPrompts.map((item) => (
                        <button
                            key={item}
                            type="button"
                            onClick={() => {
                                setPromptInput(item);
                                runAssistant(item);
                            }}
                            className="inline-flex min-h-10 items-center justify-center rounded-full border px-4 py-2 whitespace-normal text-left hover:bg-slate-50"
                            style={{
                                backgroundColor: '#ffffff',
                                borderColor: '#e2e8f0',
                                color: '#0f172a',
                                fontSize: '14px',
                                fontWeight: 500,
                                lineHeight: '20px',
                                minHeight: '40px',
                            }}
                        >
                            <span style={{ color: '#0f172a' }}>{item}</span>
                        </button>
                    ))}
                </div>
                <div className="mt-3 flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => runAssistant(promptInput)}
                        disabled={loading || !residentId}
                        className="inline-flex items-center gap-2 rounded-xl bg-[var(--theme-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                    >
                        {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
                        Generate insights
                    </button>
                    <span className="text-xs text-slate-500">
                        {lastRunAt ? `Updated ${lastRunAt.toLocaleTimeString()}` : 'Not generated yet'}
                    </span>
                </div>
            </div>

            {error ? (
                <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {error}
                </div>
            ) : null}

            <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                {loading ? (
                    <div className="flex items-center gap-2 text-slate-600">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Generating insights...
                    </div>
                ) : (
                    <>
                        <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <Sparkles className="h-4 w-4 text-[var(--theme-primary)]" />
                            Assistant summary
                        </div>
                        <p className="mt-2 text-sm text-slate-700 leading-6">{summary}</p>
                        <p className="mt-2 text-xs text-slate-500">
                            Mode: {assistant?.mode || 'heuristic'} {assistant?.model ? `(${assistant.model})` : ''}
                            {displayedPrompt ? ` | Prompt: ${displayedPrompt}` : ''}
                        </p>
                    </>
                )}
            </div>

            <div className="mt-5 grid gap-4 md:grid-cols-2">
                <div className="rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <FileText className="h-4 w-4 text-violet-600" />
                        Recent fax messages
                    </div>
                    <div className="mt-3 space-y-2 text-sm text-slate-700">
                        {faxMessages.length > 0 ? faxMessages.slice(0, 3).map((item) => (
                            <div key={item.id} className="rounded-lg bg-slate-50 px-3 py-2">
                                <div className="font-medium text-slate-800">{item.subject || 'Fax message'}</div>
                                <div className="text-xs text-slate-500">{item.status} · {item.direction} · {item.received_at || 'No timestamp'}</div>
                            </div>
                        )) : <div>No recent fax messages available.</div>}
                    </div>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <Pill className="h-4 w-4 text-blue-600" />
                        Medication context
                    </div>
                    <p className="mt-3 text-sm text-slate-700">{medicationSummary || 'No medication context available yet.'}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <Activity className="h-4 w-4 text-rose-600" />
                        Recent vital history
                    </div>
                    <div className="mt-3 space-y-2 text-sm text-slate-700">
                        {vitalHistory.length > 0 ? vitalHistory.slice(0, 4).map((item) => (
                            <div key={`${item.date || 'date'}-${item.temperature || 'temp'}-${item.pulse || 'pulse'}`} className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                <span>{item.date}</span>
                                <span>{item.temperature ?? '—'}°F · {item.pulse ?? '—'} bpm · {item.oxygen_saturation ?? '—'}%</span>
                            </div>
                        )) : <div>No recent vital history available.</div>}
                    </div>
                </div>
            </div>

            <div className="mt-5 grid gap-4 md:grid-cols-2">
                <div className="rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <Lightbulb className="h-4 w-4 text-amber-500" />
                        Key insights
                    </div>
                    <ul className="mt-3 space-y-2 text-sm text-slate-700">
                        {insights.length > 0 ? insights.map((item) => <li key={item} className="leading-6">• {item}</li>) : <li>No insights available yet.</li>}
                    </ul>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <ShieldCheck className="h-4 w-4 text-emerald-600" />
                        Recommended next steps
                    </div>
                    <ul className="mt-3 space-y-2 text-sm text-slate-700">
                        {recommendations.length > 0 ? recommendations.map((item) => <li key={item} className="leading-6">• {item}</li>) : <li>No recommendations available yet.</li>}
                    </ul>
                </div>
            </div>

            <div className="mt-5 rounded-xl border border-slate-200 p-4">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <BrainCircuit className="h-4 w-4 text-[var(--theme-primary)]" />
                        Multi-agent workflow
                    </div>
                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${approvalRequired ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`}>
                        {approvalRequired ? 'Human review required' : 'No manual escalation'}
                    </span>
                </div>

                {workflowLoading ? (
                    <div className="mt-3 flex items-center gap-2 text-sm text-slate-600">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Running workflow agents...
                    </div>
                ) : (
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        {agentResults.length > 0 ? agentResults.map((agent) => {
                            const lines = [...(agent.findings ?? []), ...(agent.actions ?? [])];
                            return (
                                <div key={agent.skill} className="rounded-lg bg-slate-50 px-3 py-3">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{agent.skill || 'agent'}</div>
                                    <ul className="mt-2 space-y-1 text-sm text-slate-700">
                                        {lines.length > 0 ? lines.map((line, idx) => (
                                            <li key={`${agent.skill}-${idx}`} className="leading-6">• {line}</li>
                                        )) : <li>No flagged items.</li>}
                                    </ul>
                                </div>
                            );
                        }) : <div className="text-sm text-slate-600">Workflow output will appear after chart context is loaded.</div>}
                    </div>
                )}
            </div>

            <div className="mt-5 rounded-xl border border-slate-200 p-4">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <MessageSquare className="h-4 w-4 text-[var(--theme-primary)]" />
                    Follow-up chat
                </div>

                <div className="mt-3 grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
                    <div className="rounded-lg border border-slate-200 bg-white p-2">
                        <button
                            type="button"
                            onClick={startNewConversation}
                            className="mb-2 w-full rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:opacity-90"
                        >
                            + New conversation
                        </button>
                        <div className="max-h-56 space-y-1 overflow-y-auto">
                            {conversationsLoading ? (
                                <div className="px-2 py-2 text-xs text-slate-500">Loading conversations...</div>
                            ) : conversations.length > 0 ? (
                                conversations.map((conversation) => (
                                    <button
                                        key={conversation.id}
                                        type="button"
                                        onClick={() => loadConversation(conversation.id)}
                                        className={`w-full rounded-lg px-2 py-2 text-left text-xs transition ${conversationId === conversation.id ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'}`}
                                    >
                                        <div className="line-clamp-2 font-semibold">{formatConversationTitle(conversation)}</div>
                                        <div className={`mt-1 text-[11px] ${conversationId === conversation.id ? 'text-slate-300' : 'text-slate-500'}`}>
                                            {conversation.updated_at ? new Date(conversation.updated_at).toLocaleString() : 'No activity yet'}
                                        </div>
                                    </button>
                                ))
                            ) : (
                                <div className="px-2 py-2 text-xs text-slate-500">No saved conversations yet.</div>
                            )}
                        </div>
                    </div>

                    <div>
                        <div className="max-h-52 space-y-2 overflow-y-auto rounded-lg bg-slate-50 p-3">
                            {conversationMessages.length > 0 ? conversationMessages.slice(-8).map((message, idx) => (
                                <div
                                    key={`message-${idx}`}
                                    className={`rounded-lg px-3 py-2 text-sm ${message.role === 'assistant' ? 'bg-emerald-50 text-emerald-900' : message.role === 'system' ? 'bg-slate-100 text-slate-700' : 'bg-white text-slate-900'}`}
                                >
                                    <div className="mb-1 text-[11px] font-semibold uppercase tracking-wide opacity-70">{message.role || 'message'}</div>
                                    <div className="leading-6">{message.content || '...'}</div>
                                </div>
                            )) : <div className="text-sm text-slate-500">No follow-up messages yet.</div>}
                        </div>

                        <div className="mt-3 flex gap-2">
                            <input
                                type="text"
                                value={chatInput}
                                onChange={(e) => setChatInput(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        sendMessage();
                                    }
                                }}
                                placeholder="Ask a follow-up question about this resident"
                                className="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                            />
                            <button
                                type="button"
                                onClick={sendMessage}
                                disabled={chatLoading || !chatInput.trim()}
                                className="inline-flex items-center gap-2 rounded-xl bg-[var(--theme-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                            >
                                {chatLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
