<template>
  <main class="not-found">
    <h1>404</h1>
    <h2>Page not found</h2>
    <p>The page you're looking for doesn't exist.</p>
    <RouterLink to="/">Go home</RouterLink>
  </main>
</template>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-family: Arial, sans-serif;
  text-align: center;
  background: #f3f4f6;
}

h1 {
  margin: 0;
  color: #6c63ff;
  font-size: 64px;
}

a {
  color: #6c63ff;
  font-weight: 600;
}
</style>