document.addEventListener('DOMContentLoaded', () => {

    const replyButtons = document.querySelectorAll('.reply-button');

    replyButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            const commentId = button.dataset.commentId;
            const replyFormContainer = document.getElementById(`reply-form-${commentId}`);

            if (replyFormContainer) {
                document.querySelectorAll('.reply-form-container').forEach(form => {
                    if (form.id !== `reply-form-${commentId}`) {
                        form.style.display = 'none';
                    }
                });

                if (replyFormContainer.style.display === 'none' || replyFormContainer.style.display === '') {
                    replyFormContainer.style.display = 'block';
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

    const toggleRepliesButtons = document.querySelectorAll('.toggle-replies-btn');

    toggleRepliesButtons.forEach(button => {
        button.addEventListener('click', () => {
            const commentId = button.dataset.commentId;
            const repliesCount = button.dataset.repliesCount;
            const repliesContainer = document.getElementById(`replies-container-${commentId}`);

            if (repliesContainer) {
                if (repliesContainer.classList.contains('hidden')) {
                    repliesContainer.classList.remove('hidden');
                    button.textContent = 'Hide Replies';
                } else {
                    repliesContainer.classList.add('hidden');
                    button.textContent = `See Replies (${repliesCount})`;
                }
            }
        });
    });
});