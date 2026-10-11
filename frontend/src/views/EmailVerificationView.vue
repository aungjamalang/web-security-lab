<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'

const route = useRoute()

const verified = computed(() => route.query.status === 'success')
</script>

<template>
  <main class="verification-page">
    <section class="verification-card">
      <div v-if="verified" class="status-icon success-icon">
        ✓
      </div>

      <div v-else class="status-icon error-icon">
        !
      </div>

      <template v-if="verified">
        <h1>Email verified!</h1>
        <p>
          Your email address has been verified successfully.
          You can now sign in to Web Security Lab.
        </p>
        <RouterLink to="/login" class="action-link">
          Go to login
        </RouterLink>
      </template>

      <template v-else>
        <h1>Verification unsuccessful</h1>
        <p>
          This verification link is invalid or has expired.
          Please request a new verification email.
        </p>
        <RouterLink to="/login" class="action-link">
          Back to login
        </RouterLink>
      </template>
    </section>
  </main>
</template>

<style scoped>
.verification-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f3f4f6;
  font-family: Arial, sans-serif;
}

.verification-card {
  width: 100%;
  max-width: 440px;
  box-sizing: border-box;
  padding: 36px;
  border-radius: 12px;
  background: white;
  text-align: center;
  box-shadow: 0 8px 28px rgb(0 0 0 / 8%);
}

.status-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 20px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  font-size: 30px;
  font-weight: 700;
}

.success-icon {
  background: #dcfce7;
  color: #15803d;
}

.error-icon {
  background: #fee2e2;
  color: #b91c1c;
}

h1 {
  margin: 0 0 12px;
}

p {
  color: #6b7280;
  line-height: 1.6;
}

.action-link {
  display: inline-block;
  margin-top: 16px;
  padding: 12px 20px;
  border-radius: 6px;
  background: #6c63ff;
  color: white;
  font-weight: 600;
  text-decoration: none;
}
</style>