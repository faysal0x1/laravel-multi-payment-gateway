# Complete Payment Process Guide

This guide demonstrates a complete payment process implementation with order management.

## Table of Contents
- [Database Structure](#database-structure)
- [Models](#models)
- [Controllers](#controllers)
- [React Components](#react-components)
- [API Integration](#api-integration)
- [Complete Flow](#complete-flow)

## Database Structure

### 1. Orders Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->json('billing_address');
            $table->json('shipping_address');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
```

### 2. Order Items Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('product_id');
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
```

## Models

### 1. Order Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'total_amount',
        'currency',
        'status',
        'billing_address',
        'shipping_address',
        'notes'
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'total_amount' => 'decimal:2'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}
```

### 2. Order Item Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

## Controllers

### 1. Order Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use faysal0x1\PaymentGateway\Facades\PaymentGateway;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'payment_gateway' => 'required|string'
        ]);

        try {
            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'user_id' => auth()->id(),
                'total_amount' => $this->calculateTotal($validated['items']),
                'currency' => 'BDT',
                'status' => 'pending',
                'billing_address' => $validated['billing_address'],
                'shipping_address' => $validated['shipping_address']
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Initiate payment
            $payment = PaymentGateway::gateway($validated['payment_gateway'])->pay([
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'order_id' => $order->order_number,
                'success_url' => route('payment.success'),
                'fail_url' => route('payment.fail'),
                'cancel_url' => route('payment.cancel'),
                'ipn_url' => route('payment.ipn'),
                'customer_id' => auth()->id(),
                'customer_name' => auth()->user()->name,
                'customer_email' => auth()->user()->email,
                'customer_phone' => auth()->user()->phone,
            ]);

            return response()->json([
                'order' => $order,
                'payment_url' => $payment['redirect_url']
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculateTotal($items)
    {
        return collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    }
}
```

## React Components

### 1. Checkout Form Component

```jsx
// src/components/CheckoutForm.jsx
import React, { useState } from 'react';
import {
    Box,
    Stepper,
    Step,
    StepLabel,
    Button,
    Typography,
    Paper
} from '@mui/material';
import CartSummary from './CartSummary';
import AddressForm from './AddressForm';
import PaymentForm from './PaymentForm';

const steps = ['Cart Summary', 'Shipping Address', 'Payment'];

const CheckoutForm = () => {
    const [activeStep, setActiveStep] = useState(0);
    const [formData, setFormData] = useState({
        items: [],
        billing_address: {},
        shipping_address: {},
        payment_gateway: ''
    });

    const handleNext = () => {
        setActiveStep((prevStep) => prevStep + 1);
    };

    const handleBack = () => {
        setActiveStep((prevStep) => prevStep - 1);
    };

    const handleSubmit = async () => {
        try {
            const response = await axios.post('/api/orders', formData);
            if (response.data.payment_url) {
                window.location.href = response.data.payment_url;
            }
        } catch (error) {
            console.error('Checkout failed:', error);
        }
    };

    const renderStepContent = (step) => {
        switch (step) {
            case 0:
                return <CartSummary items={formData.items} />;
            case 1:
                return (
                    <AddressForm
                        billingAddress={formData.billing_address}
                        shippingAddress={formData.shipping_address}
                        onUpdate={(addresses) => setFormData({ ...formData, ...addresses })}
                    />
                );
            case 2:
                return (
                    <PaymentForm
                        onSelect={(gateway) => setFormData({ ...formData, payment_gateway: gateway })}
                    />
                );
            default:
                return null;
        }
    };

    return (
        <Box sx={{ maxWidth: 800, mx: 'auto', mt: 4 }}>
            <Paper sx={{ p: 3 }}>
                <Stepper activeStep={activeStep} sx={{ mb: 4 }}>
                    {steps.map((label) => (
                        <Step key={label}>
                            <StepLabel>{label}</StepLabel>
                        </Step>
                    ))}
                </Stepper>

                {renderStepContent(activeStep)}

                <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 3 }}>
                    <Button
                        disabled={activeStep === 0}
                        onClick={handleBack}
                    >
                        Back
                    </Button>
                    {activeStep === steps.length - 1 ? (
                        <Button
                            variant="contained"
                            onClick={handleSubmit}
                        >
                            Place Order
                        </Button>
                    ) : (
                        <Button
                            variant="contained"
                            onClick={handleNext}
                        >
                            Next
                        </Button>
                    )}
                </Box>
            </Paper>
        </Box>
    );
};

export default CheckoutForm;
```

### 2. Cart Summary Component

```jsx
// src/components/CartSummary.jsx
import React from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Typography,
    Box
} from '@mui/material';

const CartSummary = ({ items }) => {
    const calculateTotal = () => {
        return items.reduce((total, item) => total + (item.quantity * item.unit_price), 0);
    };

    return (
        <Box>
            <Typography variant="h6" gutterBottom>
                Cart Summary
            </Typography>
            
            <TableContainer component={Paper}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Product</TableCell>
                            <TableCell align="right">Quantity</TableCell>
                            <TableCell align="right">Price</TableCell>
                            <TableCell align="right">Subtotal</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {items.map((item) => (
                            <TableRow key={item.product_id}>
                                <TableCell>{item.product_name}</TableCell>
                                <TableCell align="right">{item.quantity}</TableCell>
                                <TableCell align="right">${item.unit_price}</TableCell>
                                <TableCell align="right">
                                    ${item.quantity * item.unit_price}
                                </TableCell>
                            </TableRow>
                        ))}
                        <TableRow>
                            <TableCell colSpan={3} align="right">
                                <strong>Total:</strong>
                            </TableCell>
                            <TableCell align="right">
                                <strong>${calculateTotal()}</strong>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>
        </Box>
    );
};

export default CartSummary;
```

### 3. Address Form Component

```jsx
// src/components/AddressForm.jsx
import React from 'react';
import {
    Box,
    TextField,
    Grid,
    Typography,
    Checkbox,
    FormControlLabel
} from '@mui/material';

const AddressForm = ({ billingAddress, shippingAddress, onUpdate }) => {
    const [sameAsBilling, setSameAsBilling] = React.useState(true);

    const handleBillingChange = (field) => (event) => {
        const newBillingAddress = {
            ...billingAddress,
            [field]: event.target.value
        };
        onUpdate({ billing_address: newBillingAddress });
        
        if (sameAsBilling) {
            onUpdate({ shipping_address: newBillingAddress });
        }
    };

    const handleShippingChange = (field) => (event) => {
        onUpdate({
            shipping_address: {
                ...shippingAddress,
                [field]: event.target.value
            }
        });
    };

    return (
        <Box>
            <Typography variant="h6" gutterBottom>
                Billing Address
            </Typography>
            
            <Grid container spacing={3}>
                <Grid item xs={12} sm={6}>
                    <TextField
                        required
                        fullWidth
                        label="First Name"
                        value={billingAddress.first_name || ''}
                        onChange={handleBillingChange('first_name')}
                    />
                </Grid>
                <Grid item xs={12} sm={6}>
                    <TextField
                        required
                        fullWidth
                        label="Last Name"
                        value={billingAddress.last_name || ''}
                        onChange={handleBillingChange('last_name')}
                    />
                </Grid>
                <Grid item xs={12}>
                    <TextField
                        required
                        fullWidth
                        label="Address"
                        value={billingAddress.address || ''}
                        onChange={handleBillingChange('address')}
                    />
                </Grid>
                <Grid item xs={12} sm={6}>
                    <TextField
                        required
                        fullWidth
                        label="City"
                        value={billingAddress.city || ''}
                        onChange={handleBillingChange('city')}
                    />
                </Grid>
                <Grid item xs={12} sm={6}>
                    <TextField
                        required
                        fullWidth
                        label="Postal Code"
                        value={billingAddress.postal_code || ''}
                        onChange={handleBillingChange('postal_code')}
                    />
                </Grid>
            </Grid>

            <FormControlLabel
                control={
                    <Checkbox
                        checked={sameAsBilling}
                        onChange={(e) => setSameAsBilling(e.target.checked)}
                    />
                }
                label="Shipping address same as billing"
                sx={{ mt: 2 }}
            />

            {!sameAsBilling && (
                <>
                    <Typography variant="h6" gutterBottom sx={{ mt: 3 }}>
                        Shipping Address
                    </Typography>
                    
                    <Grid container spacing={3}>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                required
                                fullWidth
                                label="First Name"
                                value={shippingAddress.first_name || ''}
                                onChange={handleShippingChange('first_name')}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                required
                                fullWidth
                                label="Last Name"
                                value={shippingAddress.last_name || ''}
                                onChange={handleShippingChange('last_name')}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <TextField
                                required
                                fullWidth
                                label="Address"
                                value={shippingAddress.address || ''}
                                onChange={handleShippingChange('address')}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                required
                                fullWidth
                                label="City"
                                value={shippingAddress.city || ''}
                                onChange={handleShippingChange('city')}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                required
                                fullWidth
                                label="Postal Code"
                                value={shippingAddress.postal_code || ''}
                                onChange={handleShippingChange('postal_code')}
                            />
                        </Grid>
                    </Grid>
                </>
            )}
        </Box>
    );
};

export default AddressForm;
```

## Complete Flow

1. User adds items to cart
2. User proceeds to checkout
3. User fills in shipping and billing information
4. User selects payment gateway
5. System creates order in database
6. System initiates payment
7. User is redirected to payment gateway
8. After payment:
   - Success: Order status updated to 'paid'
   - Failure: Order status updated to 'failed'
   - Cancel: Order status remains 'pending'

## API Routes

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
});
```

## Error Handling

```php
// app/Exceptions/Handler.php
public function register()
{
    $this->renderable(function (\Exception $e) {
        if ($e instanceof PaymentException) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    });
}
```

This documentation provides a complete guide to implementing a full payment process with order management. The implementation includes:

1. Database structure for orders and order items
2. Models with relationships
3. Controllers for order processing
4. React components for the checkout process
5. API integration
6. Error handling

For more information about specific features or customization options, please refer to the main documentation. 