# Multi-Agent Care Assistant Workflow

This feature adds a lightweight multi-agent workflow for resident care review using the existing HomeLogic360 chart context.

## Agents
- Clinical review agent: inspects vitals and highlights urgent observations.
- Medication safety agent: reviews medication adherence issues and active medication orders.

## Tooling and context
The workflow uses existing resident chart data as tool-like context sources:
- vitals
- medications
- behavior charts
- sleep
- appointments

## Safety and human oversight
- Authentication is required through Sanctum.
- Authorization is enforced by the controller using the `agent:access` token ability.
- Sensitive actions require human approval when critical vitals or missed medication issues are detected.

## API
POST /api/v1/charts/assistant/{resident}/workflow
