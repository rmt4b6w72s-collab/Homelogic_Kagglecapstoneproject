import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('React Error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen flex items-center justify-center bg-gray-50 p-6">
                    <div className="max-w-md w-full bg-white rounded-lg shadow p-8">
                        <h1 className="text-2xl font-bold text-red-600 mb-4">Application Error</h1>
                        <p className="text-gray-700 mb-4">
                            Something went wrong. Please check the browser console for details.
                        </p>
                        {this.state.error && (
                            <details className="mt-4">
                                <summary className="cursor-pointer text-sm text-gray-600 mb-2">
                                    Error Details
                                </summary>
                                <pre className="text-xs bg-gray-100 p-4 rounded overflow-auto">
                                    {this.state.error.toString()}
                                    {this.state.error.stack}
                                </pre>
                            </details>
                        )}
                        <button
                            onClick={() => window.location.reload()}
                            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Reload Page
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;

