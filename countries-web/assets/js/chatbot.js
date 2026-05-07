import { CountriesApi } from "./api.js";

const $ = (sel) => document.querySelector(sel);

function appendBubble(messagesEl, text, role) {
  const div = document.createElement("div");
  div.className = `chatBubble ${role === "user" ? "chatUser" : "chatBot"}`;
  div.textContent = text;
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

export function initChatbot() {
  const form = $("#chatForm");
  const input = $("#chatInput");
  const messagesEl = $("#chatMessages");
  const sendBtn = $("#chatSendBtn");

  if (!form || !input || !messagesEl || !sendBtn) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const question = String(input.value || "").trim();
    if (!question) return;

    appendBubble(messagesEl, question, "user");
    input.value = "";
    input.focus();
    sendBtn.disabled = true;

    try {
      const data = await CountriesApi.chat(question);
      appendBubble(messagesEl, data?.answer || "Mình chưa có câu trả lời phù hợp.");
    } catch (err) {
      appendBubble(messagesEl, err?.message || "Không thể kết nối trợ lý lúc này.");
    } finally {
      sendBtn.disabled = false;
    }
  });
}

