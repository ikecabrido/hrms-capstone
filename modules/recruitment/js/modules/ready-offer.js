document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll(".ready-offer-form");

  forms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      Swal.fire({
        title: "Are you sure?",
        text: "You are about to mark this candidate as Ready for Offer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#6366f1",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, proceed!",
        cancelButtonText: "Cancel",
      }).then(function (result) {
        if (result.isConfirmed) {
          // Submit normally so PHP can redirect correctly
          form.submit();
        }
      });
    });
  });
});
