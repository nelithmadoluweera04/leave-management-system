// Function to switch tabs
function openTab(event, tabName) {
  // Hide all tab contents
  const tabContents = document.getElementsByClassName("tab-content");
  for (let i = 0; i < tabContents.length; i++) {
    tabContents[i].classList.remove("active");
  }

  // Remove active class from all tab buttons
  const tabBtns = document.getElementsByClassName("tab-btn");
  for (let i = 0; i < tabBtns.length; i++) {
    tabBtns[i].classList.remove("active");
  }

  // Show selected tab and set button as active
  document.getElementById(tabName).classList.add("active");
  event.currentTarget.classList.add("active");
}

document.addEventListener("DOMContentLoaded", () => {
  const editBtn = document.getElementById("editBtn");
  const saveBtn = document.getElementById("saveBtn");
  const profileForm = document.getElementById("profileForm");
  const inputs = profileForm.querySelectorAll("input");

  const imageUpload = document.getElementById("imageUpload");
  const profileImage = document.getElementById("profileImage");
  const headerAvatar = document.getElementById("headerAvatar");

  // Toggle Edit/Save Mode
  editBtn.addEventListener("click", () => {
    inputs.forEach(input => input.disabled = false);
    editBtn.style.display = "none";
    saveBtn.style.display = "inline-flex";
  });

  // Handle Form Submission / Save
  profileForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // Disable form fields again
    inputs.forEach(input => input.disabled = true);

    // Update dynamic text across the page
    const firstName = document.getElementById("firstName").value;
    const lastName = document.getElementById("lastName").value;
    const email = document.getElementById("email").value;
    const phone = document.getElementById("phone").value;

    const fullName = `${firstName} ${lastName}`;
    
    document.getElementById("displayName").textContent = fullName;
    document.getElementById("headerUserName").textContent = fullName;
    document.getElementById("displayEmail").textContent = email;
    document.getElementById("displayPhone").textContent = phone;

    // Toggle button views
    saveBtn.style.display = "none";
    editBtn.style.display = "inline-flex";

    alert("Profile updated successfully!");
  });

  // Profile Image Upload Preview
  imageUpload.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        profileImage.src = e.target.result;
        headerAvatar.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Logout button action
  document.getElementById("logoutBtn").addEventListener("click", (e) => {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
      // Redirect to login page
      window.location.href = "login.html"; 
    }
  });
});