// حظر الاستماع للأحداث المهملة قبل تهيئة Quill
if (window.Quill) {
  const blockedEvents = ['DOMNodeInserted', 'DOMNodeRemoved', 'DOMSubtreeModified'];
  const originalAddEventListener = EventTarget.prototype.addEventListener;
  EventTarget.prototype.addEventListener = function (type, listener, options) {
    if (!blockedEvents.includes(type)) {
      return originalAddEventListener.call(this, type, listener, options);
    }
  };

  // تهيئة إعدادات KaTeX إذا كانت موجودة
  if (window.katex) {
    window.katex.options = {
      throwOnError: false,
      strict: false,
    };
  }
}

// دالة إنشاء Toast بشكل ديناميكي
const createToast = (message, type = 'success') => {
  const toastEl = document.createElement('div');
  toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');
  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  document.body.appendChild(toastEl);

  const toast = new bootstrap.Toast(toastEl, { animation: true, autohide: true, delay: 3000 });
  toast.show();
};

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
  // Handle message deletion
  document.querySelectorAll('.confirm-delete').forEach(button => {
    button.addEventListener('click', async () => {
      const messageId = button.dataset.messageId;
      const modal = button.closest('.modal');
      
      try {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Deleting...';

        const response = await fetch(`${window.location.pathname}/${messageId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
          }
        });

        if (response.ok) {
          const data = await response.json();
          const bsModal = bootstrap.Modal.getInstance(modal);
          bsModal.hide();
          
          // Remove the message from the list
          const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
          if (messageElement) {
            messageElement.remove();
          }
          
          createToast(data.message || 'Message deleted successfully', 'success');
          
          // Optionally reload the page to update counters
          window.location.reload();
        } else {
          throw new Error('Failed to delete message');
        }
      } catch (error) {
        console.error('Error deleting message:', error);
        createToast('An error occurred while deleting the message', 'danger');
      } finally {
        button.disabled = false;
        button.innerHTML = '<i class="ti ti-trash me-1"></i> Delete';
      }
    });
  });

  // إصلاح aria-hidden للنوافذ المنبثقة
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('shown.bs.modal', () => {
      modal.removeAttribute('aria-hidden');
    });
  });

  // تهيئة كل نافذة محرر Quill مرتبطة بمودال
  document.querySelectorAll('[id^="messageModal"]').forEach(modal => {
    const messageId = modal.id.replace('messageModal', '');
    const editorElement = document.querySelector(`#editor${messageId}`);
    if (!editorElement) return;

    const quillEditor = new Quill(editorElement, {
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['link'],
          ['clean'],
        ],
      },
      placeholder: 'Type your reply here...',
      theme: 'snow',
    });

    const quickReplyForm = modal.querySelector('.quick-reply-form');
    const replyToggle = modal.querySelector('.message-reply-toggle');
    const replyForm = modal.querySelector('.message-reply-form');

    if (quickReplyForm) {
      quickReplyForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const editorContent = quillEditor.root.innerHTML.trim();
        if (!editorContent) {
          alert('Please enter a reply message.');
          return;
        }

        const quickReplyBtn = quickReplyForm.querySelector('.quick-reply-btn');
        quickReplyBtn.disabled = true;
        quickReplyBtn.innerHTML = '<i class="ti ti-send me-1"></i> Sending...';

        // Create a hidden input for the message content
        const messageInput = document.createElement('input');
        messageInput.type = 'hidden';
        messageInput.name = 'message';
        messageInput.value = editorContent;
        quickReplyForm.appendChild(messageInput);

        try {
          const formData = new FormData(quickReplyForm);
          const response = await fetch(quickReplyForm.action, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            body: formData,
          });

          const data = await response.json();
          if (data.success) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
            createToast(data.message || 'Message sent successfully', 'success');
            quillEditor.setText('');
            
            // Optionally reload the page to show the updated messages
            window.location.reload();
          } else {
            throw new Error(data.message || 'Failed to send message');
          }
        } catch (error) {
          console.error('Error sending message:', error);
          createToast(error.message || 'An error occurred while sending the message.', 'danger');
        } finally {
          quickReplyBtn.disabled = false;
          quickReplyBtn.innerHTML = '<i class="ti ti-send me-1"></i> Send Reply';
        }
      });
    }

    if (replyToggle && replyForm) {
      replyToggle.addEventListener('click', () => {
        replyForm.classList.toggle('show');
        if (replyForm.classList.contains('show')) {
          quillEditor.focus();
        }
      });
    }
  });
});
