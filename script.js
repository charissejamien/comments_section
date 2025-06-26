// script.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Reply Form Toggle Logic ---
    const replyButtons = document.querySelectorAll('.reply-button');

    replyButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent default link behavior (page jump)

            const commentId = button.dataset.commentId;
            const replyFormContainer = document.getElementById(`reply-form-${commentId}`);

            if (replyFormContainer) {
                // Hide all other reply forms first
                document.querySelectorAll('.reply-form-container').forEach(form => {
                    if (form.id !== `reply-form-${commentId}`) {
                        form.style.display = 'none';
                    }
                });

                // Toggle visibility of the clicked reply form
                if (replyFormContainer.style.display === 'none' || replyFormContainer.style.display === '') {
                    replyFormContainer.style.display = 'block';
                    // Optional: Focus on the input field
                    const inputField = replyFormContainer.querySelector('input[type="text"]');
                    if (inputField) {
                        inputField.focus();
                    }
                } else {
                    replyFormContainer.style.display = 'none';
                }
            }
        });
    });

    // --- See/Collapse Replies Toggle Logic ---
    const toggleRepliesButtons = document.querySelectorAll('.toggle-replies-btn');

    toggleRepliesButtons.forEach(button => {
        button.addEventListener('click', () => {
            const commentId = button.dataset.commentId;
            const repliesCount = button.dataset.repliesCount;
            const repliesContainer = document.getElementById(`replies-container-${commentId}`);

            if (repliesContainer) {
                if (repliesContainer.classList.contains('hidden')) {
                    // Show replies
                    repliesContainer.classList.remove('hidden');
                    button.textContent = 'Hide Replies';
                } else {
                    // Hide replies
                    repliesContainer.classList.add('hidden');
                    button.textContent = `See Replies (${repliesCount})`;
                }
            }
        });
    });
});