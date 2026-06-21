/**
 * VA Auto Sales — AI Customer Support Chat (Groq)
 */
(function () {
  'use strict';

  var chat = document.getElementById('ai-chat');
  var panel = document.getElementById('ai-chat-panel');
  var launcher = document.getElementById('ai-chat-launcher');
  var closeBtn = document.getElementById('ai-chat-close');
  var form = document.getElementById('ai-chat-form');
  var input = document.getElementById('ai-chat-input');
  var messagesEl = document.getElementById('ai-chat-messages');
  var quickBtns = document.querySelectorAll('.ai-chat__quick-btn');
  var footerOpenChat = document.getElementById('footer-open-chat');

  if (!chat) return;

  var conversation = [];
  var isLoading = false;
  var apiFromAttr = chat.getAttribute('data-api-url') || '';
  var API_URL = apiFromAttr;
  if (!API_URL) {
    var base = (window.APP_BASE || '').replace(/\/$/, '');
    API_URL = (base ? base + '/' : '/') + 'Backend/api/chat.php';
  }

  function openChat() {
    if (window.VA_AI_CHAT) {
      window.VA_AI_CHAT.open();
      return;
    }
    chat.classList.add('is-open');
    chat.setAttribute('aria-hidden', 'false');
    if (launcher) launcher.setAttribute('aria-expanded', 'true');
    document.body.classList.add('ai-chat-open');
    if (input) setTimeout(function () { input.focus(); }, 300);
  }

  function closeChat() {
    if (window.VA_AI_CHAT) {
      window.VA_AI_CHAT.close();
      return;
    }
    chat.classList.remove('is-open');
    chat.setAttribute('aria-hidden', 'true');
    if (launcher) launcher.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('ai-chat-open');
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function appendMessage(role, text) {
    var wrap = document.createElement('div');
    wrap.className = 'ai-chat__msg ai-chat__msg--' + (role === 'user' ? 'user' : 'bot');

    var bubble = document.createElement('div');
    bubble.className = 'ai-chat__bubble';
    bubble.textContent = text;

    wrap.appendChild(bubble);
    messagesEl.appendChild(wrap);
    scrollToBottom();
  }

  function showTyping() {
    var el = document.createElement('div');
    el.className = 'ai-chat__msg ai-chat__msg--bot ai-chat__typing';
    el.id = 'ai-chat-typing';
    el.innerHTML = '<div class="ai-chat__bubble"><span></span><span></span><span></span></div>';
    messagesEl.appendChild(el);
    scrollToBottom();
  }

  function hideTyping() {
    var el = document.getElementById('ai-chat-typing');
    if (el) el.remove();
  }

  function setLoading(state) {
    isLoading = state;
    input.disabled = state;
    document.getElementById('ai-chat-send').disabled = state;
  }

  function sendMessage(text) {
    text = (text || '').trim();
    if (!text || isLoading) return;

    appendMessage('user', text);
    conversation.push({ role: 'user', content: text });
    input.value = '';

    setLoading(true);
    showTyping();

    var fetchUrl = API_URL + (API_URL.indexOf('?') === -1 ? '?stream=true' : '&stream=true');

    fetch(fetchUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ messages: conversation }),
    })
      .then(function (response) {
        hideTyping();
        if (!response.ok) {
          throw new Error('Network response error');
        }
        var reader = response.body.getReader();
        var decoder = new TextDecoder('utf-8');
        var accumulated = '';
        var botReply = '';

        // Create the bot message bubble in the DOM first
        var wrap = document.createElement('div');
        wrap.className = 'ai-chat__msg ai-chat__msg--bot';
        var bubble = document.createElement('div');
        bubble.className = 'ai-chat__bubble';
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        scrollToBottom();

        function read() {
          return reader.read().then(function (result) {
            if (result.done) {
              if (!botReply) {
                bubble.textContent = 'Sorry, I could not respond right now.';
              } else {
                conversation.push({ role: 'assistant', content: botReply });
              }
              setLoading(false);
              input.focus();
              return;
            }

            accumulated += decoder.decode(result.value, { stream: true });
            var lines = accumulated.split('\n');
            accumulated = lines.pop(); // keep last partial line in buffer

            for (var i = 0; i < lines.length; i++) {
              var line = lines[i].trim();
              if (line === '') continue;
              if (line === 'data: [DONE]') {
                continue;
              }
              if (line.indexOf('data: ') === 0) {
                try {
                  var data = JSON.parse(line.substring(6));
                  if (data.text) {
                    botReply += data.text;
                    bubble.textContent = botReply;
                    scrollToBottom();
                  } else if (data.error) {
                    botReply += ' [Error: ' + data.error + ']';
                    bubble.textContent = botReply;
                    scrollToBottom();
                  }
                } catch (e) {
                  // ignore JSON parse errors of incomplete chunks
                }
              }
            }
            return read();
          });
        }
        return read();
      })
      .catch(function () {
        hideTyping();
        appendMessage('assistant', 'Connection error. Please try again or contact us on WhatsApp.');
        setLoading(false);
        input.focus();
      });
  }

  if (!window.VA_AI_CHAT && launcher) {
    launcher.addEventListener('click', function () {
      if (chat.classList.contains('is-open')) closeChat();
      else openChat();
    });
    if (closeBtn) closeBtn.addEventListener('click', closeChat);
    if (footerOpenChat) footerOpenChat.addEventListener('click', openChat);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && chat.classList.contains('is-open')) closeChat();
    });
  }

  if (!form || !input || !messagesEl) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    sendMessage(input.value);
  });

  quickBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      sendMessage(btn.getAttribute('data-prompt'));
    });
  });

})();
