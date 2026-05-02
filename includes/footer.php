<?php if (isLoggedIn()): ?>
    </div><!-- .content-wrapper -->
</div><!-- .main-content -->

<!-- AI Chat Widget -->
<button class="chat-toggle" id="chatToggle" title="AI Helpdesk Assistant">
    <i class="fas fa-robot"></i>
</button>
<div class="chat-panel" id="chatPanel">
    <div class="chat-header">
        <i class="fas fa-robot fa-lg"></i>
        <div>
            <div class="chat-title">SmartDesk AI Assistant</div>
            <div class="chat-subtitle">Ask me about IT support</div>
        </div>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="chat-msg bot">
            <div class="msg-bubble">Hi! I'm your SmartDesk assistant. How can I help you today?</div>
        </div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Type your question..." autocomplete="off">
        <button onclick="sendChatMessage()" id="chatSendBtn"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/app.js"></script>
</body>
</html>
