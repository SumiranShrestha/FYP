$(document).ready(function () {
    $("#loginForm").submit(function (event) {
        event.preventDefault();
        $.post("login_process.php", $(this).serialize(), function (response) {
            $("#login-message").html(response.message);
            if (response.status === "success") setTimeout(() => location.reload(), 1000);
        }, "json");
    });

    $("#signupForm").submit(function (event) {
        event.preventDefault();
        $.post("signup_process.php", $(this).serialize(), function (response) {
            $("#signup-message").html(response.message);
            if (response.status === "success") setTimeout(() => location.reload(), 1000);
        }, "json");
    });
});
