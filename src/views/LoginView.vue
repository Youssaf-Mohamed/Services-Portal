<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h2>Services Portal</h2>
      </div>
      
      <div class="content">
        <p>Please log in via Batechu to access the portal.</p>
        
        <a href="http://batechu.com/" class="login-button">
          Go to Batechu
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
// Login logic removed as per request to use SSO only
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: var(--color-background);
  padding: 1rem;
}

.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 420px;
  text-align: center;
}

.login-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #edf2f7;
}

.login-header h2 {
  color: #2d3748;
  font-size: 24px;
  margin: 0;
}

.content p {
  color: #4a5568;
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

.login-button {
  display: inline-block;
  width: 100%;
  padding: 1rem;
  background-color: var(--color-secondary, #667eea);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: transform 0.2s, box-shadow 0.2s;
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
