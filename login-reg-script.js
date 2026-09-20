function showForm(formID){
  document.querySelectorAll(".card").forEach(form => form.classList.remove("active"));
  document.getElementById(formID).classList.add("active");
}
