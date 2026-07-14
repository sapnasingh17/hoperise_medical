<div class="scroll-to-top scroll-to-target" data-target=".header-top">
  <span class="icon fa fa-angle-up"></span>
</div>

<script src="plugins/jquery.js"></script>
<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="plugins/bootstrap-select.min.js"></script>
<!-- Slick Slider -->
<script src="plugins/slick/slick.min.js"></script>
<!-- FancyBox -->
<script src="plugins/fancybox/jquery.fancybox.min.js"></script>
<!-- Google Map -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCC72vZw-6tGqFyRhhg5CkF2fqfILn2Tsw"></script>
<script src="plugins/google-map/gmap.js"></script>

<script src="plugins/validate.js"></script>
<script src="plugins/wow.js"></script>
<script src="plugins/jquery-ui.js"></script>
<script src="plugins/timePicker.js"></script>
<script src="js/script.js"></script>

<!-- AI Chatbot Logic -->
<script>
$(document).ready(function() {
    const $fab = $('#ai-chat-fab');
    const $container = $('#ai-chat-container');
    const $closeBtn = $('#ai-chat-close');
    const $input = $('#ai-chat-input');
    const $sendBtn = $('#ai-chat-send');
    const $messagesContainer = $('#ai-chat-messages');
    const $chatBody = $('#ai-chat-body');
    const $typingIndicator = $('#ai-typing-indicator');
    
    let historyLoaded = false;
    
    // Helper to format messages (basic markdown to html)
    function formatMessageText(text) {
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        
        // Link matching [text](url)
        escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-primary fw-bold text-decoration-underline">$1</a>');
        
        // Bold matching **text**
        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // Bullets
        escaped = escaped.replace(/^(?:-|\*)\s+(.+)$/gm, '• $1');
        
        // Newlines
        escaped = escaped.replace(/\n\n/g, '</div><div class="mt-2">').replace(/\n/g, '<br>');
        
        return escaped;
    }
    
    function scrollToBottom() {
        $chatBody.animate({ scrollTop: $chatBody[0].scrollHeight }, 300);
    }
    
    function appendMessage(sender, text) {
        const isUser = (sender === 'user');
        const wrapperClass = isUser ? 'ai-msg-wrapper user-msg-wrapper align-self-end' : 'ai-msg-wrapper d-flex gap-2';
        const bubbleStyle = isUser ? '' : 'border-radius: 4px 16px 16px 16px;';
        
        let html = '';
        if (isUser) {
            html = `
                <div class="${wrapperClass} mb-3">
                    <div class="ai-msg-bubble">
                        ${formatMessageText(text)}
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="${wrapperClass} mb-3">
                    <div class="ai-msg-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="ai-msg-bubble" style="${bubbleStyle}">
                        ${formatMessageText(text)}
                    </div>
                </div>
            `;
        }
        
        $messagesContainer.append(html);
        scrollToBottom();
    }
    
    function loadChatHistory() {
        $.ajax({
            url: 'api_chat.php?action=history',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.history.length > 0) {
                    $messagesContainer.empty();
                    response.history.forEach(function(chat) {
                        appendMessage(chat.sender, chat.message);
                    });
                }
                historyLoaded = true;
            },
            error: function() {
                historyLoaded = true; // prevent infinite loading attempts
            }
        });
    }
    
    function sendMessage(msgText) {
        if (!msgText) return;
        
        // Append user message
        appendMessage('user', msgText);
        
        // Show typing indicator
        $typingIndicator.removeClass('d-none');
        scrollToBottom();
        
        $.ajax({
            url: 'api_chat.php?action=send',
            type: 'POST',
            data: JSON.stringify({ message: msgText }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                $typingIndicator.addClass('d-none');
                if (response.success) {
                    appendMessage('ai', response.response);
                } else {
                    appendMessage('ai', 'Sorry, I encountered an error: ' + (response.error || 'Unknown error'));
                }
            },
            error: function() {
                $typingIndicator.addClass('d-none');
                appendMessage('ai', 'Sorry, I cannot connect to the server right now. Please try again later.');
            }
        });
    }
    
    // Toggle Chat
    $fab.on('click', function() {
        $container.toggleClass('active');
        $fab.find('.fab-icon-open').toggleClass('d-none');
        $fab.find('.fab-icon-close').toggleClass('d-none');
        
        if ($container.hasClass('active') && !historyLoaded) {
            loadChatHistory();
        }
    });
    
    // Close Chat
    $closeBtn.on('click', function() {
        $container.removeClass('active');
        $fab.find('.fab-icon-open').removeClass('d-none');
        $fab.find('.fab-icon-close').addClass('d-none');
    });
    
    // Send event handlers
    $sendBtn.on('click', function() {
        const text = $input.val().trim();
        if (text) {
            sendMessage(text);
            $input.val('');
        }
    });
    
    $input.on('keyup', function(e) {
        if (e.key === 'Enter') {
            const text = $input.val().trim();
            if (text) {
                sendMessage(text);
                $input.val('');
            }
        }
    });
    
    // Quick chips click
    $(document).on('click', '.ai-chat-chip', function() {
        const prompt = $(this).attr('data-prompt');
        if (prompt) {
            sendMessage(prompt);
        }
    });
});
</script>
</body>
</html>