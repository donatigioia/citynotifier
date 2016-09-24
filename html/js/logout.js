function change()
{
document.location.href="index.html";
}
function logout()
{
        $.ajax({
                type: 'POST',
            url: 'logout.php',
            async: false
                }).success
                        (
                        function(response)
                        {
                        change();
                        }
                        );
}
