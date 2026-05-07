<?php declare(strict_types=1); ?>
<div class="card chatCard">
  <div class="hd">
    <h2>Trợ lý Gemini</h2>
    <div class="status">Hỏi về thủ đô, dân số, vị trí, khu vực, văn hóa quốc gia...</div>
  </div>
  <div class="bd">
    <div id="chatMessages" class="chatMessages" aria-live="polite">
      <div class="chatBubble chatBot">Xin chào! Mình là trợ lý quốc gia. Bạn muốn tìm hiểu quốc gia nào?</div>
    </div>
    <form id="chatForm" class="chatForm">
      <input
        id="chatInput"
        name="question"
        type="text"
        maxlength="600"
        placeholder="Ví dụ: Thủ đô của Canada là gì?"
        autocomplete="off"
        required
      />
      <button class="authBtn" id="chatSendBtn" type="submit">Gửi</button>
    </form>
  </div>
</div>

