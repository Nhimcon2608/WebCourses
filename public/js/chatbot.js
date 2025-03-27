const aiBubble = document.getElementById("ai-bubble");
const chatWidget = document.getElementById("chat-widget");
const minimizeBtn = document.getElementById("chat-minimize");
const sendBtn = document.getElementById("chat-send");
const chatInput = document.getElementById("chat-input");
const msgContainer = document.getElementById("chat-messages");
const chatFiles = document.getElementById("chat-files");

// Thêm overlay với spinner
const loadingOverlay = document.createElement("div");
loadingOverlay.classList.add("loading-overlay");
loadingOverlay.innerHTML = '<div class="spinner"></div>';
document.body.appendChild(loadingOverlay); // Thêm vào body thay vì msgContainer

// Hàm cập nhật vị trí spinner
function updateSpinnerPosition() {
  if (!loadingOverlay.classList.contains("active")) return;

  const rect = msgContainer.getBoundingClientRect();
  const scrollTop = msgContainer.scrollTop;
  const containerHeight = msgContainer.clientHeight;

  // Tính toán vị trí trung tâm của khung chat trong viewport
  const top = rect.top + containerHeight / 2 - loadingOverlay.offsetHeight / 2;
  const left = rect.left + rect.width / 2 - loadingOverlay.offsetWidth / 2;

  // Cập nhật vị trí spinner
  loadingOverlay.style.top = `${top}px`;
  loadingOverlay.style.left = `${left}px`;
  loadingOverlay.style.width = `${rect.width}px`;
  loadingOverlay.style.height = `${containerHeight}px`;
}

// Cập nhật vị trí khi cuộn hoặc thay đổi kích thước
msgContainer.addEventListener("scroll", updateSpinnerPosition);
window.addEventListener("resize", updateSpinnerPosition);

aiBubble.addEventListener("click", () => {
  chatWidget.classList.toggle("minimized");
  chatWidget.classList.toggle("expanded");
  if (chatWidget.classList.contains("expanded")) updateSpinnerPosition();
});

minimizeBtn.addEventListener("click", () => {
  chatWidget.classList.remove("expanded");
  chatWidget.classList.add("minimized");
});

let isDragging = false;
let offset = { x: 0, y: 0 };

chatWidget.addEventListener("mousedown", (e) => {
  if (!chatWidget.classList.contains("expanded")) return;
  isDragging = true;
  offset.x = e.clientX - chatWidget.offsetLeft;
  offset.y = e.clientY - chatWidget.offsetTop;
  chatWidget.style.transition = "none";
});

document.addEventListener("mousemove", (e) => {
  if (isDragging) {
    chatWidget.style.left = `${e.clientX - offset.x}px`;
    chatWidget.style.top = `${e.clientY - offset.y}px`;
    chatWidget.style.right = "auto";
    chatWidget.style.bottom = "auto";
    updateSpinnerPosition(); // Cập nhật vị trí spinner khi kéo
  }
});

document.addEventListener("mouseup", () => {
  if (isDragging) {
    isDragging = false;
    chatWidget.style.transition = "all 0.3s ease";
  }
});

document.querySelectorAll(".suggestion-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    chatInput.value = btn.getAttribute("data-question");
    sendMessage();
  });
});

function getFormattedTime() {
  const now = new Date();
  return `${now.getHours().toString().padStart(2, "0")}:${now
    .getMinutes()
    .toString()
    .padStart(2, "0")}`;
}

document.querySelectorAll(".timestamp").forEach((ts) => {
  ts.textContent = getFormattedTime();
});

sendBtn.addEventListener("click", sendMessage);
chatInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

let isThinking = false;

// Hàm xử lý dữ liệu phản hồi từ server
async function processServerResponse(response) {
  if (!response.ok) {
    console.error(
      "Server response not OK:",
      response.status,
      response.statusText
    );
    return {
      reply: `Lỗi máy chủ: ${response.status}. Vui lòng thử lại sau.`,
      error: true,
    };
  }

  let text = "";
  try {
    text = await response.text();
    console.log("Raw server response:", text);

    if (!text || text.trim() === "") {
      return {
        reply: "Máy chủ trả về dữ liệu rỗng. Vui lòng thử lại sau.",
        error: true,
      };
    }

    // Thử loại bỏ các ký tự không hợp lệ nếu có
    const jsonStartPos = text.indexOf("{");
    const jsonEndPos = text.lastIndexOf("}") + 1;

    if (jsonStartPos >= 0 && jsonEndPos > jsonStartPos) {
      // Cắt để lấy phần JSON hợp lệ
      const jsonText = text.substring(jsonStartPos, jsonEndPos);
      console.log("Extracted JSON:", jsonText);

      try {
        return JSON.parse(jsonText);
      } catch (extractedJsonError) {
        console.error("Error parsing extracted JSON:", extractedJsonError);
        // Không ném lỗi, chuyển đến bước xử lý tiếp theo
      }
    }

    try {
      return JSON.parse(text);
    } catch (jsonError) {
      console.error("JSON parsing error:", jsonError, "Response text:", text);

      // Tạo response mặc định nếu parsing thất bại
      return {
        reply:
          "Xin lỗi, đã xảy ra lỗi khi xử lý phản hồi. Vui lòng thử lại sau.",
        error: true,
      };
    }
  } catch (error) {
    console.error("Error processing response:", error, "Response text:", text);
    return {
      reply: "Lỗi khi xử lý phản hồi từ máy chủ. Vui lòng thử lại sau.",
      error: true,
    };
  }
}

async function sendMessage() {
  if (isThinking) return;

  const userText = chatInput.value.trim();
  const files = chatFiles.files;
  if (!userText && files.length === 0) return;

  const userDiv = document.createElement("div");
  userDiv.classList.add("message", "user-message");
  let messageContent = userText ? `<span>${escapeHtml(userText)}</span>` : "";
  let fileHtml = "";

  if (files.length > 0) {
    fileHtml = '<div class="file-list">';
    for (let file of files) {
      const fileURL = URL.createObjectURL(file);
      fileHtml += `<a href="${fileURL}" target="_blank" class="file-item">${file.name}</a>`;
      if (file.type.startsWith("image/")) {
        fileHtml += `<img src="${fileURL}" class="file-preview-img" alt="${file.name}">`;
      }
    }
    fileHtml += "</div>";
  }

  userDiv.innerHTML = `
        <div class="gpt-bubble">
            <svg class="message-icon" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            ${messageContent}${fileHtml}
            <span class="timestamp">${getFormattedTime()}</span>
        </div>
    `;
  msgContainer.appendChild(userDiv);
  msgContainer.scrollTop = msgContainer.scrollHeight;
  chatInput.value = "";

  sendBtn.classList.add("sending");
  setTimeout(() => sendBtn.classList.remove("sending"), 300);

  // Bật spinner và khóa input
  isThinking = true;
  console.log("Showing spinner");
  loadingOverlay.classList.add("active");
  updateSpinnerPosition(); // Cập nhật vị trí spinner khi bật
  sendBtn.disabled = true;
  chatInput.disabled = true;

  const formData = new FormData();
  formData.append("prompt", userText);

  // Lấy CSRF token an toàn
  let csrfToken = "";
  const csrfInput = document.querySelector('input[name="csrf_token"]');
  if (csrfInput) {
    csrfToken = csrfInput.value;
  } else {
    console.warn("CSRF token không tìm thấy trong DOM");
  }

  formData.append("csrf_token", csrfToken);

  for (let file of files) {
    formData.append("files[]", file);
  }

  // Đếm số lần thử
  let retryCount = 0;
  const maxRetries = 2;

  async function attemptSendRequest() {
    try {
      // Đảm bảo BASE_URL được định nghĩa
      const baseUrl = window.BASE_URL || "/";
      console.log(
        `Sending request attempt ${retryCount + 1} to:`,
        baseUrl + "chatbot/processChat"
      );

      const response = await fetch(baseUrl + "chatbot/processChat", {
        method: "POST",
        body: formData,
      });

      // Xử lý dữ liệu phản hồi với hàm riêng
      let data;
      try {
        data = await processServerResponse(response);
      } catch (parseError) {
        console.error("Error processing response:", parseError);
        // Tạo phản hồi mặc định thay vì ném lỗi
        data = {
          reply:
            "Xin lỗi, đã xảy ra lỗi khi xử lý phản hồi. Vui lòng thử lại sau.",
          error: true,
        };
      }

      // Kiểm tra dữ liệu phản hồi
      if (!data || !data.reply) {
        console.error("Invalid response data:", data);
        // Tạo phản hồi mặc định thay vì ném lỗi
        data = {
          reply:
            "Xin lỗi, dữ liệu phản hồi không hợp lệ. Vui lòng thử lại sau.",
          error: true,
        };
      }

      // Hiển thị phản hồi
      const botDiv = document.createElement("div");
      botDiv.classList.add("message", "bot-message");
      botDiv.innerHTML = `
        <div class="gpt-bubble">
          <svg class="message-icon" viewBox="0 0 1024 1024" fill="currentColor">
            <path d="M512.3 462.6m-253.7 0a253.7 253.7 0 1 0 507.4 0 253.7 253.7 0 1 0-507.4 0Z"/>
          </svg>
          <span>${
            data.error
              ? `<span style="color:red;">${data.reply}</span>`
              : data.reply
          }</span>
          <span class="timestamp">${getFormattedTime()}</span>
        </div>
      `;
      msgContainer.appendChild(botDiv);
      msgContainer.scrollTop = msgContainer.scrollHeight;
      return true; // Success
    } catch (error) {
      console.error(`Request attempt ${retryCount + 1} failed:`, error);

      // Thử lại nếu chưa quá số lần tối đa
      if (retryCount < maxRetries) {
        retryCount++;
        console.log(`Retrying... Attempt ${retryCount + 1}`);
        return await attemptSendRequest();
      }

      // Hiển thị thông báo lỗi sau khi đã thử hết số lần
      const errDiv = document.createElement("div");
      errDiv.classList.add("message", "bot-message");
      errDiv.innerHTML = `
        <div class="gpt-bubble">
          <svg class="message-icon" viewBox="0 0 1024 1024" fill="currentColor">
            <path d="M512.3 462.6m-253.7 0a253.7 253.7 0 1 0 507.4 0 253.7 253.7 0 1 0-507.4 0Z"/>
          </svg>
          <span style="color:red;">Xin lỗi, tôi không thể xử lý yêu cầu của bạn lúc này. Vui lòng thử lại sau.</span>
          <span class="timestamp">${getFormattedTime()}</span>
        </div>
      `;
      msgContainer.appendChild(errDiv);
      msgContainer.scrollTop = msgContainer.scrollHeight;
      return false; // Failed after all retries
    }
  }

  try {
    await attemptSendRequest();
  } finally {
    isThinking = false;
    console.log("Hiding spinner");
    loadingOverlay.classList.remove("active");
    sendBtn.disabled = false;
    chatInput.disabled = false;
    chatFiles.value = "";
  }
}

function escapeHtml(str) {
  return str
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
