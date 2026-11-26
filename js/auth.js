
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.password-toggle').forEach(button => {

        button.addEventListener('click', () => {

            const input = document.getElementById(
                button.dataset.target
            );

            if (!input) return;

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';

            button.textContent = show ? 'Hide' : 'Show';

            button.setAttribute(
                'aria-pressed',
                String(show)
            );

            button.setAttribute(
                'aria-label',
                show ? 'Hide password' : 'Show password'
            );

        });

    });

});
