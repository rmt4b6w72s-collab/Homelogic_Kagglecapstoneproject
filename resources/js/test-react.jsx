// Simple test to verify React is working
import React from 'react';
import ReactDOM from 'react-dom/client';

console.log('Test React file loaded');

const testElement = document.getElementById('react-app');
if (testElement) {
    console.log('Test: React root element found');
    try {
        const root = ReactDOM.createRoot(testElement);
        root.render(
            React.createElement('div', { 
                style: { padding: '20px', textAlign: 'center', background: 'white', minHeight: '100vh' } 
            }, 
                React.createElement('h1', { style: { color: 'green' } }, 'React is Working!'),
                React.createElement('p', null, 'If you see this, React loaded successfully.'),
                React.createElement('button', { 
                    onClick: () => alert('React click handler works!'),
                    style: { padding: '10px 20px', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '5px', cursor: 'pointer', marginTop: '20px' }
                }, 'Test Button')
            )
        );
        console.log('Test: React rendered successfully');
    } catch (error) {
        console.error('Test: Error rendering React:', error);
        testElement.innerHTML = `<div style="padding: 20px; color: red;"><h1>React Test Error</h1><p>${error.message}</p></div>`;
    }
} else {
    console.error('Test: React root element not found');
}

