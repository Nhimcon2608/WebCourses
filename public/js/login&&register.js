document.addEventListener("DOMContentLoaded", function () {
  var modal = document.getElementById("loginModal");
  var loginBtn = document.getElementById("login-btn");
  var closeBtn = document.querySelector(".modal .close");
  var signupLink = document.getElementById("signup-link");
  var forgotLink = document.getElementById("forgot-link");

  // Kiểm tra xem người dùng đã đăng nhập chưa
  var isLoggedIn = document.querySelector(".btn-logout") !== null;

  // Nếu đã đăng nhập, ẩn modal và không xử lý các event đăng nhập
  if (isLoggedIn) {
    if (modal) modal.style.display = "none";
    return;
  }

  // Tham chiếu đến các form
  var loginForm = document.getElementById("login-form");
  var signupForm = document.getElementById("signup-form");

  // Tự động mở modal nếu có thông báo thành công hoặc lỗi
  var successMessages = document.querySelectorAll(".success-message");
  var errorMessages = document.querySelectorAll(".error-message");
  var hasVerificationCodeInput =
    document.querySelectorAll('input[name="verification_code"]').length > 0;

  // Access the flag from PHP
  var hasVerificationCode = window.hasVerificationCode || false;

  // Show login modal if there are messages
  if (
    successMessages.length > 0 ||
    errorMessages.length > 0 ||
    hasVerificationCode ||
    hasVerificationCodeInput
  ) {
    showModal();

    // Hiệu ứng hiển thị modal
    animateAuthForm();

    // Show login form if redirected after registration
    if (hasVerificationCode || hasVerificationCodeInput) {
      // If the form is not already rendered, create it
      if (!hasVerificationCodeInput) {
        document.querySelector(".auth-form-content").innerHTML = `
          <form action="${
            window.BASE_URL
          }auth/resetPassword" method="POST" class="auth-form-content">
            <h2 class="auth-title">Xác Nhận Mã</h2>
            <input type="hidden" name="csrf_token" value="${
              document.querySelector('input[name="csrf_token"]').value
            }">
            <div class="auth-input-group">
              <i class="fas fa-key"></i>
              <input type="text" name="verification_code" class="auth-input" placeholder="Nhập mã xác nhận" required>
            </div>
            <div class="auth-input-group">
              <i class="fas fa-lock"></i>
              <input type="password" name="new_password" class="auth-input" placeholder="Mật khẩu mới" required>
            </div>
            <button type="submit" name="reset-password" class="auth-submit">Đặt Lại Mật Khẩu</button>
            <p class="auth-footer"><a href="#" class="link" id="login-link">Quay lại đăng nhập</a></p>
          </form>
        `;

        // Re-attach event listener for login link
        document
          .getElementById("login-link")
          .addEventListener("click", function (e) {
            e.preventDefault();
            location.reload();
          });
      } else {
        // Ensure login link in verification form has event listener
        const loginLink = document.getElementById("login-link");
        if (loginLink) {
          loginLink.addEventListener("click", function (e) {
            e.preventDefault();
            location.reload();
          });
        }
      }
    }
    // Show forgot password form when action is forgot or reset
    else if (hasVerificationCode || hasVerificationCodeInput) {
      // Create and insert forgot password form
      document.querySelector(".auth-form-content").innerHTML = `
        <form action="${
          window.BASE_URL
        }auth/forgotPassword" method="POST" class="auth-form-content">
          <h2 class="auth-title">Quên Mật Khẩu</h2>
          <input type="hidden" name="csrf_token" value="${
            document.querySelector('input[name="csrf_token"]').value
          }">
          <div class="auth-input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="forgot_email" class="auth-input" placeholder="Nhập email của bạn" required>
          </div>
          <button type="submit" name="forgot_password" class="auth-submit">Gửi Mã Xác Nhận</button>
          <p class="auth-footer"><a href="#" class="link" id="login-link">Quay lại đăng nhập</a></p>
        </form>
      `;

      // Re-attach event listener for login link
      document
        .getElementById("login-link")
        .addEventListener("click", function (e) {
          e.preventDefault();
          location.reload();
        });
    }
  }

  // Hiển thị modal khi click vào nút đăng nhập
  if (loginBtn) {
    loginBtn.addEventListener("click", function (e) {
      e.preventDefault();
      // Hiển thị form đăng nhập, ẩn form đăng ký
      showLoginForm();
      showModal();
      animateAuthForm();
    });
  }

  // Đóng modal khi click vào nút đóng
  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      hideModal();
    });
  }

  // Đóng modal khi click ra ngoài modal
  window.addEventListener("click", function (event) {
    if (event.target == modal) {
      hideModal();
    }
  });

  // Ngăn form resubmission khi refresh trang
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href.split("?")[0]);
  }

  // Xử lý form submission để tránh resubmission
  document.addEventListener("submit", function (e) {
    const form = e.target;
    // Chỉ xử lý các form liên quan đến authentication
    if (
      form.querySelector('[name="login"]') ||
      form.querySelector('[name="register"]') ||
      form.querySelector('[name="forgot_password"]') ||
      form.querySelector('[name="reset-password"]')
    ) {
      // Thêm timestamp để đảm bảo mỗi request là duy nhất
      const timestamp = new Date().getTime();
      const timestampInput = document.createElement("input");
      timestampInput.type = "hidden";
      timestampInput.name = "timestamp";
      timestampInput.value = timestamp;
      form.appendChild(timestampInput);
    }
  });

  // Chuyển sang form đăng ký
  if (signupLink) {
    signupLink.addEventListener("click", function (e) {
      e.preventDefault();
      showSignupForm();
    });
  }

  // Xử lý các login link để chuyển về form đăng nhập
  document.querySelectorAll("#login-link").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      showLoginForm();
    });
  });

  // Chuyển sang form quên mật khẩu
  if (forgotLink) {
    forgotLink.addEventListener("click", function (e) {
      e.preventDefault();
      showForgotPasswordForm();
    });
  }

  // Hàm hiển thị modal
  function showModal() {
    modal.style.display = "flex";
  }

  // Hàm ẩn modal
  function hideModal() {
    gsap.to(".auth-form", {
      opacity: 0,
      y: -20,
      duration: 0.3,
      ease: "power2.out",
      onComplete: () => {
        modal.style.display = "none";
        gsap.set(".auth-form", { clearProps: "all" });
      },
    });
  }

  // Hàm hiệu ứng cho auth form
  function animateAuthForm() {
    gsap.fromTo(
      ".auth-form",
      { opacity: 0, y: -20 },
      { opacity: 1, y: 0, duration: 0.4, ease: "power2.out" }
    );
  }

  // Hàm hiển thị form đăng nhập
  function showLoginForm() {
    gsap.to(".auth-form-content", {
      opacity: 0,
      duration: 0.2,
      ease: "power2.out",
      onComplete: () => {
        if (loginForm) loginForm.style.display = "flex";
        if (signupForm) signupForm.style.display = "none";
        gsap.to(".auth-form-content", {
          opacity: 1,
          duration: 0.3,
          ease: "power2.out",
        });
      },
    });
  }

  // Hàm hiển thị form đăng ký
  function showSignupForm() {
    gsap.to(".auth-form-content", {
      opacity: 0,
      duration: 0.2,
      ease: "power2.out",
      onComplete: () => {
        if (loginForm) loginForm.style.display = "none";
        if (signupForm) signupForm.style.display = "flex";
        gsap.to(".auth-form-content", {
          opacity: 1,
          duration: 0.3,
          ease: "power2.out",
        });
      },
    });
  }

  // Hàm hiển thị form quên mật khẩu
  function showForgotPasswordForm() {
    gsap.to(".auth-form-wrapper", {
      opacity: 0,
      duration: 0.2,
      ease: "power2.out",
      onComplete: () => {
        document.querySelector(".auth-form-wrapper").innerHTML = `
        <form action="${
          window.BASE_URL
        }auth/forgotPassword" method="POST" class="auth-form-content">
          <h2 class="auth-title">Quên Mật Khẩu</h2>
          <input type="hidden" name="csrf_token" value="${
            document.querySelector('input[name="csrf_token"]').value
          }">
          <div class="auth-input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="forgot_email" class="auth-input" placeholder="Nhập email của bạn" required>
          </div>
          <button type="submit" name="forgot_password" class="auth-submit">Gửi Mã Xác Nhận</button>
          <p class="auth-footer"><a href="#" class="link" id="login-link">Quay lại đăng nhập</a></p>
        </form>
      `;

        // Re-attach event listener for login link
        document
          .getElementById("login-link")
          .addEventListener("click", function (e) {
            e.preventDefault();
            showLoginForm();
          });

        gsap.to(".auth-form-wrapper", {
          opacity: 1,
          duration: 0.3,
          ease: "power2.out",
        });
      },
    });
  }
});
