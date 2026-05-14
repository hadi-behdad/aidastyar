document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-freediet-form]').forEach(function (wrapper) {
        var form = wrapper.querySelector('[data-fd-form]');

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('فرم FreeDiet با موفقیت ثبت شد.');
        });
    });
});