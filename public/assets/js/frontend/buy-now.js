// buy now
$(document).ready(function() {
    const withBase = window.pdWithBase || function (path) {
        const basePath = (window.location.pathname.split('/').filter(Boolean)[0] === 'phonesdukan') ? '/phonesdukan' : '';
        if (!path) return basePath + '/';
        if (/^https?:\/\//i.test(path) || path.startsWith('//')) return path;
        if (path.startsWith(basePath + '/')) return path;
        if (path.startsWith('/')) return basePath + path;
        return basePath + '/' + path;
    };

    $(".buy-button").click(function() {
        let productId = $(this).data("product-id");
        let quantity = 1; // Default quantity as 1 for direct purchase

        $.ajax({
            url: withBase("/app/Controllers/CartController.php"),
            type: "POST",
            data: JSON.stringify({
                product_id: productId,
                quantity: quantity
            }),
            contentType: "application/json",
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    window.location.href = withBase("/checkout"); // Redirect to checkout after adding to cart
                } else {
                    Swal.fire({
                        title: "Oops!",
                        text: response.message,
                        icon: "error",
                        confirmButtonText: "Try Again"
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong, please try again.",
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        });
    });
});
