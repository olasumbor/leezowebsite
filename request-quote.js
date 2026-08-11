const quoteForm = document.getElementById("quoteForm");
const quoteSuccess = document.getElementById("quoteSuccess");

if (quoteForm) {
    quoteForm.addEventListener("submit", function (event) {
        event.preventDefault();

        quoteSuccess.style.display = "block";

        quoteForm.reset();

        quoteSuccess.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    });
}