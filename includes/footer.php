<footer class="footer-main py-5 mt-5" style="background-color: #000000 !important; color: #ffffff !important;" data-bs-theme="dark">
  <div class="container">
    <div class="row g-4">
      <!-- About Widget -->
      <div class="col-lg-4 col-md-6">
        <div class="about-widget">
          <div class="d-flex align-items-center gap-2 mb-4">
            <img src="images/img.png" alt="Hope Rise Medical Logo" height="40" class="d-inline-block align-top">
            <h5 class="text-white mb-0">Hope Rise Medical</h5>
          </div>
          <p class="text-light-emphasis">Delivering premium clinical excellence and patient care. Leading orthopedic and general surgery services in India.</p>
          <ul class="list-unstyled mt-3 text-light-emphasis gap-3 d-flex flex-column">
            <li class="d-flex align-items-start">
              <i class="bi bi-geo-alt-fill text-primary me-3 mt-1"></i>
              <span>401, Solaris Royce, Opp. Old RTO, Besides Vanita Vishram Ground, Surat, India.</span>
            </li>
            <li class="d-flex align-items-center">
              <i class="bi bi-envelope-fill text-primary me-3"></i>
              <a href="mailto:support@hoperise.com" class="text-decoration-none text-light-emphasis">support@hoperise.com</a>
            </li>
            <li class="d-flex align-items-center">
              <i class="bi bi-telephone-fill text-primary me-3"></i>
              <span>+91 2215 463 895</span>
            </li>
          </ul>
        </div>
      </div>
      
      <!-- Quick Links -->
      <div class="col-lg-4 col-md-6">
        <h5 class="text-white mb-4">Quick Links</h5>
        <div class="row">
          <div class="col-6">
            <ul class="list-unstyled d-flex flex-column gap-2">
              <li><a href="about.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> About</a></li>
              <li><a href="service.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> Service</a></li>
              <li><a href="team.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> Team</a></li>
            </ul>
          </div>
          <div class="col-6">
            <ul class="list-unstyled d-flex flex-column gap-2">
              <li><a href="add_appointment.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> Booking</a></li>
              <li><a href="contact.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> Contact</a></li>
              <li><a href="patient_reg.php" class="text-decoration-none text-light-emphasis hover-primary"><i class="bi bi-chevron-right me-1"></i> Register</a></li>
            </ul>
          </div>
        </div>
        <div class="mt-4">
          <h6 class="text-white mb-3">Follow Us</h6>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-facebook"></i></a>
            <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-instagram"></i></a>
            <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
      </div>

      <!-- Recent Posts -->
      <!-- <div class="col-lg-4 col-md-12">
        <h5 class="text-white mb-4">Latest Insights</h5>
        <div class="d-flex flex-column gap-3">
          <div class="d-flex gap-3">
            <img src="images/blog/post-thumb-small.jpg" alt="Insight 1" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
            <div>
              <h6 class="text-white mb-1"><a href="#" class="text-decoration-none text-white hover-primary">Healthy Joint Tips</a></h6>
              <p class="small text-light-emphasis mb-0">Learn how to maintain active joint flexibility and avoid injuries...</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <img src="images/blog/post-thumb-small.jpg" alt="Insight 2" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
            <div>
              <h6 class="text-white mb-1"><a href="#" class="text-decoration-none text-white hover-primary">Clinical Hygiene</a></h6>
              <p class="small text-light-emphasis mb-0">Discover Hope Rise's advanced safety protocols for patients...</p>
            </div>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</footer>

<!-- AI Chatbot Floating Button -->
<div id="ai-chat-fab" class="ai-chat-fab" title="Chat with Hope Rise AI">
  <div class="ai-chat-fab-pulse"></div>
  <i class="bi bi-chat-dots-fill fab-icon-open"></i>
  <i class="bi bi-x-lg fab-icon-close d-none"></i>
  <span class="ai-chat-online-badge"></span>
</div>

<!-- AI Chat Drawer -->
<div id="ai-chat-container" class="ai-chat-container">
  <div class="ai-chat-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <div class="ai-chat-avatar-container">
        <i class="bi bi-robot text-white fs-3 bg-primary bg-gradient p-2 rounded-circle border border-2 border-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"></i>
        <span class="ai-chat-header-dot"></span>
      </div>
      <div>
        <h6 class="mb-0 text-white fw-bold">Hope Rise Assistant</h6>
        <small class="text-white-50">Online • Hospital AI Guide</small>
      </div>
    </div>
    <button type="button" class="btn-close btn-close-white" id="ai-chat-close" aria-label="Close"></button>
  </div>
  
  <div class="ai-chat-body" id="ai-chat-body">
    <!-- Initial welcome message -->
    <div class="ai-msg-wrapper d-flex gap-2 mb-3">
      <div class="ai-msg-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ai-msg-bubble">
        <p class="mb-0">Hello! I am your Hope Rise AI Assistant. How can I help you today? You can ask me about:
          <ul class="ps-3 mb-0 mt-1 small">
            <li>Available doctors & specialties</li>
            <li>Booking an appointment</li>
            <li>Hospital working hours & contact</li>
            <li>General medical information</li>
          </ul>
        </p>
      </div>
    </div>
    
    <!-- Quick Action Chips -->
    <div class="ai-chat-chips d-flex flex-wrap gap-2 mb-3 mt-2">
      <button class="ai-chat-chip" data-prompt="Can you recommend a doctor?">Recommend a Doctor</button>
      <button class="ai-chat-chip" data-prompt="What are the hospital working hours?">Hospital Hours</button>
      <button class="ai-chat-chip" data-prompt="How do I book an appointment?">Book Appointment</button>
      <button class="ai-chat-chip" data-prompt="What is the hospital helpline?">Hospital Contact</button>
    </div>
    
    <!-- Dynamic Messages Container -->
    <div id="ai-chat-messages" class="d-flex flex-column gap-3 mb-3"></div>
    
    <!-- Typing Indicator -->
    <div id="ai-typing-indicator" class="ai-msg-wrapper d-flex gap-2 mb-3 d-none">
      <div class="ai-msg-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ai-msg-bubble typing-bubble d-flex align-items-center gap-1">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </div>
  
  <div class="ai-chat-footer">
    <div class="input-group">
      <input type="text" id="ai-chat-input" class="form-control" placeholder="Ask something..." aria-label="Ask something...">
      <button class="btn btn-premium-send" type="button" id="ai-chat-send">
        <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</div>

<style>
/* AI Chatbot Styles */
.ai-chat-fab {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
  z-index: 1050;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.ai-chat-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(13, 110, 253, 0.6);
}
.ai-chat-fab-pulse {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 50%;
  border: 2px solid #0d6efd;
  animation: fab-pulse 2s infinite;
  pointer-events: none;
}
@keyframes fab-pulse {
  0% { transform: scale(1); opacity: 1; }
  100% { transform: scale(1.5); opacity: 0; }
}
.ai-chat-online-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  background-color: #198754;
  border: 2px solid white;
  border-radius: 50%;
}
.ai-chat-container {
  position: fixed;
  bottom: 100px;
  right: 30px;
  width: 380px;
  max-width: calc(100vw - 60px);
  height: 520px;
  max-height: calc(100vh - 150px);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(220, 224, 230, 0.8);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
  transform: translateY(20px);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.ai-chat-container.active {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}
.ai-chat-header {
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  color: white;
  padding: 15px 20px;
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
}
.ai-chat-avatar-container {
  position: relative;
}
.ai-chat-header-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  background-color: #198754;
  border: 2px solid #0d6efd;
  border-radius: 50%;
}
.ai-chat-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background-color: #f8f9fa;
  display: flex;
  flex-direction: column;
}
.ai-msg-wrapper {
  max-width: 85%;
}
.ai-msg-wrapper.user-msg-wrapper {
  align-self: flex-end;
}
.ai-msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #0d6efd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.ai-msg-bubble {
  background-color: white;
  color: #212529;
  padding: 10px 14px;
  border-radius: 4px 16px 16px 16px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  font-size: 14px;
  line-height: 1.4;
  word-break: break-word;
}
.user-msg-wrapper .ai-msg-bubble {
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  color: white;
  border-radius: 16px 4px 16px 16px;
}
.ai-chat-chip {
  background-color: #eef2f7;
  color: #0d6efd;
  border: 1px solid #ddecff;
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 12.5px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  margin-bottom: 5px;
}
.ai-chat-chip:hover {
  background-color: #0d6efd;
  color: white;
  border-color: #0d6efd;
}
.ai-chat-footer {
  padding: 15px;
  background-color: white;
  border-top: 1px solid #eee;
}
.btn-premium-send {
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  color: white;
  border: none;
  padding: 0 16px;
  transition: all 0.2s;
}
.btn-premium-send:hover {
  background: linear-gradient(135deg, #0b5ed7, #0a58ca);
  color: white;
  transform: translateY(-1px);
}
.typing-bubble span {
  width: 6px;
  height: 6px;
  background-color: #98a6ad;
  border-radius: 50%;
  display: inline-block;
  animation: typing-bounce 1.4s infinite ease-in-out both;
}
.typing-bubble span:nth-child(1) { animation-delay: -0.32s; }
.typing-bubble span:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing-bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1.0); }
}
</style>