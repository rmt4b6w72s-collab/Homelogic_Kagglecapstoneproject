import React from 'react';
import { AlertTriangle, RefreshCw, Home, ArrowLeft } from 'lucide-react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('React Error:', error, errorInfo);
        this.setState({ errorInfo });
    }

    handleReset = () => {
        this.setState({ hasError: false, error: null, errorInfo: null });
    };

    handleGoToDashboard = () => {
        window.location.href = '/dashboard';
    };

    render() {
        if (this.state.hasError) {
            return (
                <ErrorFallback
                    error={this.state.error}
                    errorInfo={this.state.errorInfo}
                    onReset={this.handleReset}
                    onReload={() => window.location.reload()}
                    onGoToDashboard={this.handleGoToDashboard}
                />
            );
        }

        return this.props.children;
    }
}

function ErrorFallback({ error, errorInfo, onReset, onReload, onGoToDashboard }) {
    const [showDetails, setShowDetails] = React.useState(false);

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 p-6">
            <div className="max-w-2xl w-full bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
                <div className="flex items-start space-x-4 mb-6">
                    <div className="flex-shrink-0">
                        <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <AlertTriangle className="w-6 h-6 text-red-600" />
                        </div>
                    </div>
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Something went wrong
                        </h1>
                        <p className="text-gray-600 mb-4">
                            We encountered an unexpected error. Don't worry, your data is safe. 
                            You can try refreshing the page or going back to the dashboard.
                        </p>
                    </div>
                </div>

                {error && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-sm font-medium text-red-800 mb-1">
                            Error: {error.toString()}
                        </p>
                        {error.message && error.message.includes('Failed to fetch dynamically imported module') && (
                            <p className="text-xs text-red-700 mt-2">
                                This error usually occurs when a module fails to load. The page will automatically reload to retry loading the module.
                            </p>
                        )}
                    </div>
                )}

                {errorInfo && (
                    <details className="mb-6">
                        <summary
                            className="cursor-pointer text-sm font-medium text-gray-700 mb-2 hover:text-gray-900"
                            onClick={() => setShowDetails(!showDetails)}
                        >
                            {showDetails ? 'Hide' : 'Show'} technical details
                        </summary>
                        {showDetails && (
                            <pre className="text-xs bg-gray-100 p-4 rounded-lg overflow-auto max-h-64 border border-gray-200">
                                {errorInfo.componentStack}
                            </pre>
                        )}
                    </details>
                )}

                <div className="flex flex-col sm:flex-row gap-3">
                    <button
                        onClick={onReset}
                        className="flex-1 px-4 py-2.5 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors font-medium flex items-center justify-center space-x-2"
                    >
                        <RefreshCw className="w-4 h-4" />
                        <span>Try Again</span>
                    </button>
                    <button
                        onClick={onGoToDashboard}
                        className="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center justify-center space-x-2"
                    >
                        <Home className="w-4 h-4" />
                        <span>Go to Dashboard</span>
                    </button>
                    <button
                        onClick={onReload}
                        className="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center justify-center space-x-2"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Reload Page</span>
                    </button>
                </div>

                <div className="mt-6 pt-6 border-t border-gray-200">
                    <p className="text-xs text-gray-500 text-center">
                        If this problem persists, please contact support with the error details above.
                    </p>
                </div>
            </div>
        </div>
    );
}

export default ErrorBoundary;

