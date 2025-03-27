// Toggle dark mode functionality
document.addEventListener("DOMContentLoaded", function () {
  const modeToggle = document.getElementById("mode-toggle");
  const body = document.body;

  // Check if dark mode preference is stored in localStorage
  const darkMode = localStorage.getItem("darkMode") === "true";

  // Apply dark mode if it was enabled
  if (darkMode) {
    body.classList.add("dark-mode");
    modeToggle.innerHTML = '<i class="fas fa-sun"></i>';
  }

  // Toggle dark mode when button is clicked
  modeToggle.addEventListener("click", function () {
    body.classList.toggle("dark-mode");

    // Update icon based on mode
    const isDarkMode = body.classList.contains("dark-mode");
    modeToggle.innerHTML = isDarkMode
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';

    // Save preference
    localStorage.setItem("darkMode", isDarkMode);
  });

  // Mobile menu toggle functionality
  const mobileToggle = document.getElementById("mobile-toggle");
  const sidebar = document.getElementById("sidebar");

  mobileToggle.addEventListener("click", function () {
    sidebar.classList.toggle("active");
  });

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", function (event) {
    if (
      window.innerWidth <= 992 &&
      !sidebar.contains(event.target) &&
      !mobileToggle.contains(event.target) &&
      sidebar.classList.contains("active")
    ) {
      sidebar.classList.remove("active");
    }
  });

  // Handle notification dropdown (if implemented)
  const notificationIcon = document.querySelector(".notification-icon");
  if (notificationIcon) {
    notificationIcon.addEventListener("click", function () {
      // Show notifications dropdown (to be implemented)
      console.log("Notifications clicked");
    });
  }

  // Form validation (if needed)
  const courseForm = document.querySelector("form");
  if (courseForm) {
    courseForm.addEventListener("submit", function (e) {
      // Basic validation can be added here if needed
      // For example, checking required fields
    });
  }

  // Logout functionality
  window.handleLogout = function () {
    if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
      window.location.href =
        window.location.origin + "/WebCourses/app/controllers/logout.php";
    }
  };

  // Notification dropdown
  const notifIcon = document.querySelector(".notification-icon");
  if (notifIcon) {
    notifIcon.addEventListener("click", function () {
      // In a real implementation, this would show a dropdown
      alert("Chức năng thông báo sẽ được phát triển trong tương lai!");
    });
  }

  // Course selector
  const courseSelector = document.querySelector(".course-selector select");
  if (courseSelector) {
    courseSelector.addEventListener("change", function () {
      this.form.submit();
    });
  }

  // File upload preview
  const fileInput = document.getElementById("attachment");
  if (fileInput) {
    fileInput.addEventListener("change", function () {
      // In a real implementation, this could show a preview of the selected file
      const fileName = this.files[0]?.name || "No file selected";
      // For example, add a small preview element:
      // document.querySelector('.file-preview').textContent = fileName;
    });
  }

  // Confirmation for delete actions
  const deleteButtons = document.querySelectorAll(".btn-delete");
  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      if (!confirm("Bạn có chắc chắn muốn xóa mục này?")) {
        e.preventDefault();
      }
    });
  });

  // Auto-hide messages after 5 seconds
  const messages = document.querySelectorAll(
    ".success-message, .error-message"
  );
  if (messages.length > 0) {
    setTimeout(function () {
      messages.forEach(function (message) {
        message.style.opacity = "0";
        message.style.transition = "opacity 0.5s ease-out";
        setTimeout(function () {
          message.style.display = "none";
        }, 500);
      });
    }, 5000);
  }
});
