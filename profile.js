function openTab(event, tabName) {
  const tabContents = document.getElementsByClassName("tab-content");
  for (let i = 0; i < tabContents.length; i++) {
    tabContents[i].classList.remove("active");
  }
  
  const tabBtns = document.getElementsByClassName("tab-btn");
  for (let i = 0; i < tabBtns.length; i++) {
    tabBtns[i].classList.remove("active");
  }

  document.getElementById(tabName).classList.add("active");
  event.currentTarget.classList.add("active");
}

document.addEventListener("DOMContentLoaded", () => {
  const editBtn = document.getElementById("editBtn");
  const saveBtn = document.getElementById("saveBtn");
  const imageUpload = document.getElementById("imageUpload");
  const profileImage = document.getElementById("profileImage");
  const inputs = document.querySelectorAll("#personal input, #imageUpload");

  if (editBtn && saveBtn) {
    editBtn.addEventListener("click", () => {
      inputs.forEach(input => input.disabled = false);
      editBtn.style.display = "none";
      saveBtn.style.display = "inline-flex";
    });
  }

  if (imageUpload && profileImage) {
    imageUpload.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          profileImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
});
