# Manager API Documentation

## Overview

This documentation provides details on how to use the Grocery Store Manager API. The API allows managers to retrieve order information and update order statuses through RESTful endpoints.

## Base URL

All API endpoints are accessible at:
```
http://www.teach.scam.keele.ac.uk/prin/22040610/grocery_store/api/
```

## Authentication

All manager API endpoints require authentication using a token-based approach.

**Token Parameter**: `manager_token`

**Example**: `?manager_token=manager123`

Include this parameter in all API requests to authenticate as a manager.

## API Endpoints

### 1. Retrieve Order by ID

Retrieves detailed information about a specific order.

**Endpoint**: `/orders.php`

**Method**: `GET`

**Parameters**:
- `id` (required): The order ID to retrieve
- `manager_token` (required): Authentication token

**Example Request**:
```
GET /api/orders.php?id=123&manager_token=manager123
```

**Success Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "customer": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "01234567890"
    },
    "product": {
      "id": 5,
      "name": "Chicken",
      "price": 5.99,
      "image": "chicken.jpg"
    },
    "quantity": 2,
    "total": 11.98,
    "formatted_total": "£11.98",
    "status": "pending",
    "order_date": "2025-04-15 14:23:45"
  }
}
```

**Error Responses**:
- 400 Bad Request: Invalid parameters
- 403 Forbidden: Invalid authentication token
- 404 Not Found: Order not found
- 500 Server Error: Internal server error

### 2. Retrieve All Orders

Retrieves a list of all orders in the system.

**Endpoint**: `/orders.php`

**Method**: `GET`

**Parameters**:
- `manager_token` (required): Authentication token

**Example Request**:
```
GET /api/orders.php?manager_token=manager123
```

**Success Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "order_id": 123,
      "user_id": 45,
      "product_id": 5,
      "quantity": 2,
      "order_date": "2025-04-15 14:23:45",
      "status": "pending",
      "product_name": "Chicken",
      "price": 5.99,
      "image_path": "chicken.jpg",
      "user_name": "John Doe",
      "total_price": 11.98,
      "formatted_total": "£11.98"
    },
    // More orders...
  ]
}
```

### 3. Retrieve Orders by User

Retrieves all orders placed by a specific user.

**Endpoint**: `/orders.php`

**Method**: `GET`

**Parameters**:
- `user_id` (required): The user ID to retrieve orders for
- `manager_token` (required): Authentication token

**Example Request**:
```
GET /api/orders.php?user_id=45&manager_token=manager123
```

**Success Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "order_id": 123,
      "user_id": 45,
      "product_id": 5,
      "quantity": 2,
      "order_date": "2025-04-15 14:23:45",
      "status": "pending",
      "product_name": "Chicken",
      "price": 5.99,
      "image_path": "chicken.jpg",
      "total_price": 11.98,
      "formatted_total": "£11.98"
    },
    // More orders from this user...
  ]
}
```

### 4. Update Order Status

Updates the status of an existing order.

**Endpoint**: `/orders.php`

**Method**: `PUT`

**Parameters**:
- `manager_token` (required): Authentication token (in query string)

**Request Body** (JSON):
```json
{
  "order_id": 123,
  "status": "processing",
  "manager_token": "manager123"
}
```

**Valid Status Values**:
- `pending`: Order has been received but not processed
- `processing`: Order is being prepared
- `completed`: Order has been fulfilled
- `cancelled`: Order has been cancelled

**Example Request**:
```
PUT /api/orders.php?manager_token=manager123
Content-Type: application/json

{
  "order_id": 123,
  "status": "processing",
  "manager_token": "manager123"
}
```

**Success Response** (200 OK):
```json
{
  "success": true,
  "message": "Order status updated successfully"
}
```

**Error Responses**:
- 400 Bad Request: Invalid parameters or missing required fields
- 403 Forbidden: Invalid authentication token
- 404 Not Found: Order not found
- 405 Method Not Allowed: HTTP method not supported
- 500 Server Error: Internal server error

## Error Handling

All API endpoints return standardized error responses in the following format:

```json
{
  "success": false,
  "message": "Detailed error message"
}
```

HTTP status codes are used appropriately to indicate the nature of errors:
- 400: Bad Request (invalid parameters)
- 401: Unauthorized (authentication required)
- 403: Forbidden (authentication provided but insufficient permissions)
- 404: Not Found (resource doesn't exist)
- 405: Method Not Allowed (HTTP method not supported)
- 500: Server Error (internal error)

## Testing the API

A testing tool is provided to verify API functionality. Access it at:

```
direct_test_api.php
```

This tool provides a simple interface to test various API endpoints and functions:
- Retrieving orders with proper authentication
- Testing authentication requirements
- Validating error responses for invalid inputs
- Testing order status updates

## Sample Request Using cURL

Here's how to access the API using cURL from the command line:

### Get an order by ID:
```bash
curl -X GET "http://www.teach.scam.keele.ac.uk/prin/your_username/grocery_store/api/orders.php?id=123&manager_token=manager123"
```

### Update an order status:
```bash
curl -X PUT \
  "http://www.teach.scam.keele.ac.uk/prin/your_username/grocery_store/api/orders.php?manager_token=manager123" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123, "status": "processing", "manager_token": "manager123"}'
```

## Security Considerations

- The API token should be kept confidential and only shared with authorized managers
- In a production environment, HTTPS should be used to encrypt all API requests
- The token-based authentication is a simple implementation for this assessment
- For a production system, a more robust authentication system (like OAuth 2.0) would be recommended

## Limitations

- This API implementation is for assessment purposes and has simplified authentication
- The manager token is hard-coded for demonstration purposes
- Error handling is simplified compared to what would be used in a production system

## Troubleshooting

Common issues:
- 403 Forbidden: Ensure your manager_token parameter is included and correct
- 404 Not Found: Verify the order ID exists in the database
- 400 Bad Request: Check that all required parameters are provided with valid values