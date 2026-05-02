/**
 * IT Helpdesk Ticketing System - Main JavaScript
 */

// Sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

// Chat Widget
document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chatToggle');
    const chatPanel = document.getElementById('chatPanel');
    const chatInput = document.getElementById('chatInput');

    if (chatToggle) {
        chatToggle.addEventListener('click', function() {
            chatPanel.classList.toggle('open');
            if (chatPanel.classList.contains('open') && chatInput) {
                chatInput.focus();
            }
        });
    }

    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendChatMessage();
        });
    }

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Global search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                const base = document.querySelector('meta[name="base-url"]');
                const baseUrl = base ? base.content : '';
                window.location.href = baseUrl + '/modules/tickets/index.php?search=' + encodeURIComponent(this.value.trim());
            }
        });
    }
});

// Send chat message
function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const messages = document.getElementById('chatMessages');
    const msg = input.value.trim();
    if (!msg) return;

    // Add user message
    messages.innerHTML += '<div class="chat-msg user"><div class="msg-bubble">' + escapeHtml(msg) + '</div></div>';
    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    // Show typing indicator
    const typingId = 'typing-' + Date.now();
    messages.innerHTML += '<div class="chat-msg bot" id="' + typingId + '"><div class="msg-bubble"><i class="fas fa-circle-notch fa-spin me-1"></i> Thinking...</div></div>';
    messages.scrollTop = messages.scrollHeight;

    // Determine base URL
    let baseUrl = '';
    const scripts = document.querySelectorAll('script[src*="app.js"]');
    if (scripts.length > 0) {
        const src = scripts[0].getAttribute('src');
        baseUrl = src.replace('/assets/js/app.js', '');
    }

    fetch(baseUrl + '/modules/ai/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
    })
    .then(r => r.json())
    .then(data => {
        const typing = document.getElementById(typingId);
        if (typing) typing.remove();
        const reply = data.success ? data.reply : (data.error || 'Sorry, I encountered an error. Please try again.');
        messages.innerHTML += '<div class="chat-msg bot"><div class="msg-bubble">' + escapeHtml(reply) + '</div></div>';
        messages.scrollTop = messages.scrollHeight;
    })
    .catch(() => {
        const typing = document.getElementById(typingId);
        if (typing) typing.remove();
        messages.innerHTML += '<div class="chat-msg bot"><div class="msg-bubble">Sorry, I could not connect. Please try again.</div></div>';
        messages.scrollTop = messages.scrollHeight;
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// AI Action helper with loading state
function aiAction(button, url, data, onSuccess) {
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> AI Processing...';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        button.disabled = false;
        button.innerHTML = originalHtml;
        onSuccess(result);
    })
    .catch(err => {
        button.disabled = false;
        button.innerHTML = originalHtml;
        alert('AI request failed. Please try again.');
    });
}

// Confirm delete
function confirmDelete(form, itemName) {
    if (confirm('Are you sure you want to delete this ' + (itemName || 'item') + '? This action cannot be undone.')) {
        form.submit();
    }
    return false;
}
