/**
 * RegistrationForm.js - React Registration Form Component
 *
 * Provides real-time validation for the registration form with React.
 * Validates name, phone, email, and password as the user types.
 */

class RegistrationForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            name: '',
            phone: '',
            email: '',
            password: '',
            confirmPassword: '',
            errors: {
                name: '',
                phone: '',
                email: '',
                password: '',
                confirmPassword: ''
            },
            formValid: false,
            submitting: false,
            submitError: '',
            submitSuccess: false
        };
    }
    
    // Validate individual field as user types
    validateField = (fieldName, value) => {
        let errors = {...this.state.errors};
        
        switch(fieldName) {
            case 'name':
                errors.name = 
                    value.trim() === '' 
                        ? 'Name is required' 
                        : !value.match(/^[A-Za-z\s]+$/)
                            ? 'Name should only contain letters'
                            : '';
                break;
                
            case 'phone':
                errors.phone = this.validatePhone(value);
                break;
                
            case 'email':
                errors.email =
                    value.trim() === ''
                        ? 'Email is required'
                        : !/\S+@\S+\.\S+/.test(value)
                            ? 'Email address is invalid'
                            : '';
                break;
                
            case 'password':
                errors.password =
                    value.length < 8
                        ? 'Password must be at least 8 characters'
                        : !/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/.test(value)
                            ? 'Password must include at least one uppercase letter, one lowercase letter, and one number'
                            : '';
                
                // If confirm password is not empty, validate it again
                if (this.state.confirmPassword.length > 0) {
                    errors.confirmPassword =
                        this.state.confirmPassword !== value
                            ? 'Passwords do not match'
                            : '';
                }
                break;
                
            case 'confirmPassword':
                errors.confirmPassword =
                    value !== this.state.password
                        ? 'Passwords do not match'
                        : '';
                break;
                
            default:
                break;
        }
        
        return errors;
    }
    
    // Special validation for phone number
    validatePhone(phone) {
        // Remove any non-digit characters
        const digits = phone.replace(/\D/g, '');
        
        // Check if it's exactly 10 digits 
        if (digits.length !== 10) {
            return 'Phone number must be 10 digits';
        }
        
        return '';
    }
    
    // Handle input changes and validate on the fly
    handleChange = (e) => {
        const { name, value } = e.target;
        
        // Update state with new value
        this.setState({ [name]: value }, () => {
            // Validate the field and update errors
            const errors = this.validateField(name, value);
            
            // Update state with new errors
            this.setState({ errors }, this.validateForm);
        });
    }
    
    // Validate entire form
    validateForm = () => {
        const { errors, name, phone, email, password, confirmPassword } = this.state;
        
        // Check if all fields are filled and no errors
        const formValid = 
            name.trim() !== '' &&
            phone.trim() !== '' &&
            email.trim() !== '' &&
            password.trim() !== '' &&
            confirmPassword.trim() !== '' &&
            errors.name === '' &&
            errors.phone === '' &&
            errors.email === '' &&
            errors.password === '' &&
            errors.confirmPassword === '';
            
        this.setState({ formValid });
    }
    
    // Handle form submission
    handleSubmit = (e) => {
        e.preventDefault();
        
        // If form isn't valid, don't submit
        if (!this.state.formValid) {
            this.setState({ submitError: 'Please fix the errors in the form' });
            return;
        }
        
        // Show submitting state
        this.setState({ submitting: true, submitError: '' });
        
        // Create form data for submission
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('name', this.state.name);
        formData.append('phone', this.state.phone);
        formData.append('email', this.state.email);
        formData.append('password', this.state.password);
        // Add CSRF token
        formData.append('csrf_token', window.csrfToken);
        
        // Submit the form
        fetch(window.APP_URL + '/api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Registration response:", data);
            if (data.success) {
                this.setState({
                    submitSuccess: true,
                    submitting: false
                });
                
                // Redirect to appropriate page after successful registration
                setTimeout(() => {
                    window.location.href = data.redirect || window.APP_URL + '/login';
                }, 1500);
            } else {
                this.setState({
                    submitError: data.message || 'Registration failed. Please try again.',
                    submitting: false
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.setState({
                submitError: 'An error occurred. Please try again later.',
                submitting: false
            });
        });
    }
    
    render() {
        const { errors, formValid, submitting, submitError, submitSuccess } = this.state;
        
        // If registration was successful, show success message
        if (submitSuccess) {
            return (
                <div className="alert alert-success text-center">
                    <h4 className="alert-heading">Registration Successful!</h4>
                    <p>Your account has been created successfully.</p>
                    <p>Redirecting you to login page...</p>
                </div>
            );
        }
        
        return (
            <form onSubmit={this.handleSubmit} noValidate>
                {submitError && (
                    <div className="alert alert-danger">{submitError}</div>
                )}
                
                <div className="mb-3">
                    <label htmlFor="name" className="form-label">Full Name</label>
                    <input
                        type="text"
                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                        id="name"
                        name="name"
                        value={this.state.name}
                        onChange={this.handleChange}
                        required
                    />
                    {errors.name && <div className="invalid-feedback">{errors.name}</div>}
                </div>
                
                <div className="mb-3">
                    <label htmlFor="phone" className="form-label">Phone Number</label>
                    <input
                        type="tel"
                        className={`form-control ${errors.phone ? 'is-invalid' : ''}`}
                        id="phone"
                        name="phone"
                        value={this.state.phone}
                        onChange={this.handleChange}
                        required
                    />
                    {errors.phone && <div className="invalid-feedback">{errors.phone}</div>}
                </div>
                
                <div className="mb-3">
                    <label htmlFor="email" className="form-label">Email Address</label>
                    <input
                        type="email"
                        className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                        id="email"
                        name="email"
                        value={this.state.email}
                        onChange={this.handleChange}
                        required
                    />
                    {errors.email && <div className="invalid-feedback">{errors.email}</div>}
                </div>
                
                <div className="mb-3">
                    <label htmlFor="password" className="form-label">Password</label>
                    <input
                        type="password"
                        className={`form-control ${errors.password ? 'is-invalid' : ''}`}
                        id="password"
                        name="password"
                        value={this.state.password}
                        onChange={this.handleChange}
                        required
                    />
                    {errors.password && <div className="invalid-feedback">{errors.password}</div>}
                </div>
                
                <div className="mb-3">
                    <label htmlFor="confirmPassword" className="form-label">Confirm Password</label>
                    <input
                        type="password"
                        className={`form-control ${errors.confirmPassword ? 'is-invalid' : ''}`}
                        id="confirmPassword"
                        name="confirmPassword"
                        value={this.state.confirmPassword}
                        onChange={this.handleChange}
                        required
                    />
                    {errors.confirmPassword && <div className="invalid-feedback">{errors.confirmPassword}</div>}
                </div>
                
                <div className="d-grid">
                    <button 
                        type="submit" 
                        className="btn btn-success" 
                        disabled={!formValid || submitting}
                    >
                        {submitting ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Processing...
                            </>
                        ) : 'Create Account'}
                    </button>
                </div>
                
                <div className="text-center mt-3">
                    <p>Already have an account? <a href={window.APP_URL + '/login'}>Login here</a></p>
                </div>
            </form>
        );
    }
}

// Render the component to the container
ReactDOM.render(
    <RegistrationForm />,
    document.getElementById('registration-form-container')
);