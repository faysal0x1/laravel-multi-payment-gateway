# React Integration Guide

This guide explains how to integrate the Multi-Payment Gateway package with your React frontend application.

## Table of Contents
- [Setup](#setup)
- [Basic Components](#basic-components)
- [Payment Form Component](#payment-form-component)
- [Payment Status Component](#payment-status-component)
- [Payment History Component](#payment-history-component)
- [API Integration](#api-integration)

## Setup

1. First, create a new React application or use your existing one:
```bash
npx create-react-app payment-app
cd payment-app
```

2. Install required dependencies:
```bash
npm install axios @mui/material @emotion/react @emotion/styled
```

## Basic Components

### 1. Payment Form Component

```jsx
// src/components/PaymentForm.jsx
import React, { useState } from 'react';
import { 
    Box, 
    TextField, 
    Button, 
    Select, 
    MenuItem, 
    FormControl, 
    InputLabel,
    Alert
} from '@mui/material';
import axios from 'axios';

const PaymentForm = () => {
    const [formData, setFormData] = useState({
        amount: '',
        currency: 'BDT',
        gateway: 'sslcommerz'
    });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await axios.post('/api/payment/initiate', formData);
            if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Payment initiation failed');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Box component="form" onSubmit={handleSubmit} sx={{ maxWidth: 400, mx: 'auto', mt: 4 }}>
            {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
            
            <TextField
                fullWidth
                label="Amount"
                type="number"
                value={formData.amount}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                required
                sx={{ mb: 2 }}
            />

            <FormControl fullWidth sx={{ mb: 2 }}>
                <InputLabel>Currency</InputLabel>
                <Select
                    value={formData.currency}
                    label="Currency"
                    onChange={(e) => setFormData({ ...formData, currency: e.target.value })}
                >
                    <MenuItem value="BDT">BDT</MenuItem>
                    <MenuItem value="USD">USD</MenuItem>
                </Select>
            </FormControl>

            <FormControl fullWidth sx={{ mb: 2 }}>
                <InputLabel>Payment Gateway</InputLabel>
                <Select
                    value={formData.gateway}
                    label="Payment Gateway"
                    onChange={(e) => setFormData({ ...formData, gateway: e.target.value })}
                >
                    <MenuItem value="sslcommerz">SSLCommerz</MenuItem>
                    <MenuItem value="bkash">bKash</MenuItem>
                    <MenuItem value="nagad">Nagad</MenuItem>
                </Select>
            </FormControl>

            <Button 
                type="submit" 
                variant="contained" 
                fullWidth 
                disabled={loading}
            >
                {loading ? 'Processing...' : 'Pay Now'}
            </Button>
        </Box>
    );
};

export default PaymentForm;
```

### 2. Payment Status Component

```jsx
// src/components/PaymentStatus.jsx
import React, { useEffect, useState } from 'react';
import { 
    Box, 
    Typography, 
    CircularProgress,
    Alert,
    Paper
} from '@mui/material';
import axios from 'axios';

const PaymentStatus = ({ transactionId }) => {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const checkStatus = async () => {
            try {
                const response = await axios.get(`/api/payment/status/${transactionId}`);
                setStatus(response.data);
            } catch (err) {
                setError(err.response?.data?.message || 'Failed to fetch payment status');
            } finally {
                setLoading(false);
            }
        };

        checkStatus();
    }, [transactionId]);

    if (loading) {
        return (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="200px">
                <CircularProgress />
            </Box>
        );
    }

    if (error) {
        return <Alert severity="error">{error}</Alert>;
    }

    return (
        <Paper sx={{ p: 3, mt: 2 }}>
            <Typography variant="h6" gutterBottom>
                Payment Status
            </Typography>
            <Box sx={{ mt: 2 }}>
                <Typography>
                    Transaction ID: {status?.transaction_id}
                </Typography>
                <Typography>
                    Amount: {status?.amount} {status?.currency}
                </Typography>
                <Typography>
                    Status: {status?.status}
                </Typography>
                {status?.payment_details && (
                    <Typography>
                        Payment Method: {status?.payment_details?.payment_method}
                    </Typography>
                )}
            </Box>
        </Paper>
    );
};

export default PaymentStatus;
```

### 3. Payment History Component

```jsx
// src/components/PaymentHistory.jsx
import React, { useEffect, useState } from 'react';
import {
    Box,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Typography,
    Chip
} from '@mui/material';
import axios from 'axios';

const PaymentHistory = () => {
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const fetchTransactions = async () => {
            try {
                const response = await axios.get('/api/payment/history');
                setTransactions(response.data);
            } catch (err) {
                setError(err.response?.data?.message || 'Failed to fetch payment history');
            } finally {
                setLoading(false);
            }
        };

        fetchTransactions();
    }, []);

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed':
                return 'success';
            case 'failed':
                return 'error';
            case 'pending':
                return 'warning';
            default:
                return 'default';
        }
    };

    return (
        <Box sx={{ mt: 4 }}>
            <Typography variant="h6" gutterBottom>
                Payment History
            </Typography>
            
            <TableContainer component={Paper}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Transaction ID</TableCell>
                            <TableCell>Amount</TableCell>
                            <TableCell>Gateway</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell>Date</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {transactions.map((transaction) => (
                            <TableRow key={transaction.id}>
                                <TableCell>{transaction.transaction_id}</TableCell>
                                <TableCell>
                                    {transaction.amount} {transaction.currency}
                                </TableCell>
                                <TableCell>{transaction.gateway_name}</TableCell>
                                <TableCell>
                                    <Chip 
                                        label={transaction.status}
                                        color={getStatusColor(transaction.status)}
                                        size="small"
                                    />
                                </TableCell>
                                <TableCell>
                                    {new Date(transaction.created_at).toLocaleDateString()}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>
        </Box>
    );
};

export default PaymentHistory;
```

## API Integration

### 1. API Service

```jsx
// src/services/paymentService.js
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL;

export const paymentService = {
    initiatePayment: async (data) => {
        const response = await axios.post(`${API_URL}/api/payment/initiate`, data);
        return response.data;
    },

    verifyPayment: async (transactionId) => {
        const response = await axios.get(`${API_URL}/api/payment/verify/${transactionId}`);
        return response.data;
    },

    getPaymentHistory: async () => {
        const response = await axios.get(`${API_URL}/api/payment/history`);
        return response.data;
    },

    processRefund: async (data) => {
        const response = await axios.post(`${API_URL}/api/payment/refund`, data);
        return response.data;
    }
};
```

### 2. Usage in App Component

```jsx
// src/App.jsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Container, CssBaseline, ThemeProvider, createTheme } from '@mui/material';
import PaymentForm from './components/PaymentForm';
import PaymentStatus from './components/PaymentStatus';
import PaymentHistory from './components/PaymentHistory';

const theme = createTheme();

function App() {
    return (
        <ThemeProvider theme={theme}>
            <CssBaseline />
            <Router>
                <Container>
                    <Routes>
                        <Route path="/" element={<PaymentForm />} />
                        <Route path="/payment/status/:transactionId" element={<PaymentStatus />} />
                        <Route path="/payment/history" element={<PaymentHistory />} />
                    </Routes>
                </Container>
            </Router>
        </ThemeProvider>
    );
}

export default App;
```

### 3. Environment Configuration

Create a `.env` file in your React project root:

```env
REACT_APP_API_URL=http://your-laravel-api-url
```

## Error Handling

### 1. Error Boundary Component

```jsx
// src/components/ErrorBoundary.jsx
import React from 'react';
import { Alert, Box } from '@mui/material';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    render() {
        if (this.state.hasError) {
            return (
                <Box sx={{ p: 2 }}>
                    <Alert severity="error">
                        Something went wrong. Please try again later.
                    </Alert>
                </Box>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
```

## Best Practices

1. Always use environment variables for API URLs
2. Implement proper error handling and loading states
3. Use TypeScript for better type safety
4. Implement proper form validation
5. Use proper authentication and authorization
6. Implement proper error boundaries
7. Use proper state management (Redux/Context) for larger applications

## Security Considerations

1. Always use HTTPS
2. Implement proper CORS policies
3. Validate all user inputs
4. Implement proper authentication
5. Use proper error handling
6. Implement proper logging
7. Use proper security headers

This documentation provides a complete guide to integrating the payment gateway package with your React application. For more information about specific features or customization options, please refer to the main documentation. 