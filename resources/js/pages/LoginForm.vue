<template>
  <div class="login-wrapper">
    <div class="container-fluid">
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-11 col-sm-8 col-md-6 col-lg-6 col-xl-4">
          <div class="card shadow-lg border-0 login-card">
            <!-- Header -->
            <div class="card-header bg-gradient-primary text-white text-center border-0">
              <div class="login-icon mb-3">
                <i class="bi bi-person-circle"></i>
              </div>
              <h4 class="mb-1 fw-bold">Welcome Back</h4>
              <p class="mb-0 opacity-75 small">Sign in to your account</p>
            </div>
            
            <!-- Body -->
            <div class="card-body p-4">
              <!-- Alert untuk error/success -->
              <div v-if="message.text"
                   :class="`alert alert-${message.type} alert-dismissible fade show border-0 shadow-sm`"
                   role="alert">
                <i :class="message.type === 'danger' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-check-circle-fill'" class="me-2"></i>
                {{ message.text }}
                <button type="button" class="btn-close" @click="clearMessage" aria-label="Close"></button>
              </div>

              <form @submit.prevent="handleLogin">
                <!-- Email Input -->
                <div class="mb-3">
                  <label for="email" class="form-label fw-medium">
                    <i class="bi bi-envelope me-2"></i>
                    Email Address
                  </label>
                  <input 
                    type="email"
                    id="email"
                    class="form-control form-control-lg"
                    :class="{ 'is-invalid': errors.email }"
                    v-model="form.email"
                    placeholder="Enter your email"
                    required
                    ref="emailInput"
                  >
                  <div v-if="errors.email" class="invalid-feedback">
                    {{ errors.email[0] }}
                  </div>
                </div>

                <!-- Password Input -->
                <div class="mb-3">
                  <label for="password" class="form-label fw-medium">
                    <i class="bi bi-lock me-2"></i>
                    Password
                  </label>
                  <div class="input-group input-group-lg">
                    <input 
                      :type="showPassword ? 'text' : 'password'"
                      id="password"
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                      v-model="form.password"
                      placeholder="Enter your password"
                      required
                    >
                    <button 
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="togglePassword"
                      :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                  <div v-if="errors.password" class="invalid-feedback d-block">
                    {{ errors.password[0] }}
                  </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input 
                      type="checkbox"
                      class="form-check-input"
                      id="remember"
                      v-model="form.remember"
                    >
                    <label class="form-check-label small" for="remember">
                      Remember me
                    </label>
                  </div>
                  <a href="#" class="text-decoration-none small fw-medium">
                    <i class="bi bi-question-circle me-1"></i>
                    Forgot Password?
                  </a>
                </div>

                <!-- Submit Button -->
                <button 
                  type="submit"
                  class="btn btn-primary btn-lg w-100 fw-medium login-btn"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  <i v-else class="bi bi-box-arrow-in-right me-2"></i>
                  {{ loading ? 'Signing in...' : 'Sign In' }}
                </button>
              </form>

              <!-- Footer -->
              <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 small text-muted">
                  Don't have an account?
                  <a href="#" class="text-decoration-none fw-medium ms-1">Sign up here</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'LoginForm',
  data() {
    return {
      form: {
        email: '',
        password: '',
        remember: false
      },
      errors: {},
      message: {
        text: '',
        type: ''
      },
      loading: false,
      showPassword: false
    }
  },
  methods: {
    async handleLogin() {
      try {
        this.loading = true;
        this.clearErrors();
        this.clearMessage();
        
        const response = await axios.post('/api/login', {
          email: this.form.email,
          password: this.form.password,
          remember: this.form.remember
        });

        const { token, user } = response.data.data;
        
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user_data', JSON.stringify(user));
        
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        
        setTimeout(() => {
          window.location.href = '/dashboard';
        }, 1000);
        
      } catch (error) {
        console.error('Login error:', error);
        
        if (error.response) {
          const { status, data } = error.response;
          
          if (status === 422) {
            this.errors = data.errors || {};
          } else if (status === 401) {
            this.showMessage(data.message || 'Email atau password salah', 'danger');
          } else {
            this.showMessage(data.message || 'Terjadi kesalahan server', 'danger');
          }
        } else {
          this.showMessage('Tidak dapat terhubung ke server', 'danger');
        }
      } finally {
        this.loading = false;
      }
    },
    
    togglePassword() {
      this.showPassword = !this.showPassword;
    },
    
    showMessage(text, type) {
      this.message = { text, type };
      
      if (type === 'success') {
        setTimeout(() => {
          this.clearMessage();
        }, 3000);
      }
    },
    
    clearMessage() {
      this.message = { text: '', type: '' };
    },
    
    clearErrors() {
      this.errors = {};
    }
  },
  
  mounted() {
    this.$nextTick(() => {
      if (this.$refs.emailInput) {
        this.$refs.emailInput.focus();
      }
    });
  }
}
</script>

<style scoped>
/* Login Wrapper */
.login-wrapper {
  background: linear-gradient(135deg, #667eea 0%, #b3c6e3 100%);
  min-height: 100vh;
}

/* Card Styling */
.login-card {
  border-radius: 20px;
  overflow: hidden;
  backdrop-filter: blur(10px);
  animation: slideUp 0.6s ease-out;
}

.card-header.bg-gradient-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
  padding: 1.5rem 1.5rem;
}

.login-icon {
  font-size: 3rem;
  opacity: 0.9;
}

/* Form Controls */
.form-control, .form-control-lg {
  border-radius: 12px;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.form-control:focus, .form-control-lg:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
  transform: translateY(-1px);
}

.input-group-lg .form-control {
  border-radius: 12px 0 0 12px;
}

.input-group-lg .btn {
  border-radius: 0 12px 12px 0;
  border: 2px solid #e9ecef;
  border-left: none;
}

/* Button Styling */
.login-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  border: none;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
}

.login-btn:active {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Alert Styling */
.alert {
  border-radius: 12px;
  border: none;
  font-size: 0.9rem;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
}

.alert-danger {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
}

/* Form Check */
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

/* Links */
a {
  color: #007bff;
  transition: color 0.3s ease;
}

a:hover {
  color: #0056b3;
}

/* Animations */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading Animation */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Responsive Design */
@media (max-width: 576px) {
  .login-card {
    margin: 1rem;
    border-radius: 16px;
  }
  
  .card-header.bg-gradient-primary {
    padding: 1.5rem 1rem;
  }
  
  .login-icon {
    font-size: 2.5rem;
  }
  
  .card-body {
    padding: 1.5rem !important;
  }
  
  h4 {
    font-size: 1.5rem;
  }
}

@media (max-width: 400px) {
  .login-card {
    margin: 0.5rem;
  }
  
  .card-header.bg-gradient-primary {
    padding: 1rem;
  }
  
  .card-body {
    padding: 1rem !important;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .login-wrapper {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .form-control, .form-control-lg {
    border-width: 3px;
  }
  
  .login-btn {
    border: 3px solid #ffffff;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>