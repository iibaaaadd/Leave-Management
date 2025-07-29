<template>
  <div class="main-content">
    <div class="container-fluid">
      <!-- Dashboard Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="page-title">
            <span class="title-icon">📊</span>
            Dashboard
          </h1>
          <p class="text-muted mb-0">
            Welcome back, {{ currentUser.name }}!
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm">
            <span class="me-1">📥</span>
            Export
          </button>
          <button class="btn btn-primary btn-sm">
            <span class="me-1">➕</span>
            Add New
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DashboardContent",
  props: {
    currentUser: {
      type: Object,
      required: true
    },
    stats: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatNumber(num) {
      return new Intl.NumberFormat().format(num);
    }
  }
};
</script>

<style scoped>
.main-content {
  flex: 1;
  margin-left: 250px;
  padding: 2rem;
  animation: fadeIn 0.5s ease-out;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #343a40;
  margin-bottom: 0.5rem;
}

.title-icon {
  font-size: 1.8rem;
  margin-right: 0.5rem;
}

.btn {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 1rem;
  }

  .page-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 576px) {
  .main-content {
    padding: 0.5rem;
  }
}
</style>
