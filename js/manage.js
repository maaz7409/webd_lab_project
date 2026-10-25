// Management page: validates the new-notice form and activates/deactivates
// notices without reloading the page.

let noticeForm = document.getElementById("notice-form");
let formMessage = document.getElementById("notice-form-message");
let manageMessage = document.getElementById("manage-message");
let titleInput = document.getElementById("title");
let dueDateInput = document.getElementById("due-date");
let requiresAckBox = document.getElementById("requires-ack");

const maxTitleLength = 150;

// ---------- Add-notice form ----------

function showTitleCount() {
  let remaining = maxTitleLength - titleInput.value.length;
  $("#title-count").text("(" + remaining + " characters left)");
}

// Today's date as YYYY-MM-DD, the format used by <input type="date">.
function todayText() {
  let today = new Date();
  let month = String(today.getMonth() + 1).padStart(2, "0");
  let day = String(today.getDate()).padStart(2, "0");
  return today.getFullYear() + "-" + month + "-" + day;
}

// A due date only makes sense for a task, so choosing one ticks the box.
function dueDateChanged() {
  if (dueDateInput.value !== "" && !requiresAckBox.checked) {
    requiresAckBox.checked = true;
    showStatus(formMessage, "A due date makes this notice a task, so acknowledgement has been turned on.", false);
  }
}

function validateNoticeForm(event) {
  let title = titleInput.value.trim();
  let category = document.getElementById("category-id").value;
  let body = document.getElementById("body").value.trim();
  let dueDate = dueDateInput.value;
  let problem = "";

  if (title === "") {
    problem = "Please enter a title.";
  } else if (title.length > maxTitleLength) {
    problem = "The title can be at most " + maxTitleLength + " characters long.";
  } else if (category === "") {
    problem = "Please choose a category.";
  } else if (body === "") {
    problem = "Please enter the notice text.";
  } else if (dueDate !== "" && dueDate < todayText()) {
    problem = "The due date cannot be in the past.";
  } else if (dueDate !== "" && !requiresAckBox.checked) {
    problem = "A due date can only be set for a task. Tick the acknowledgement box or clear the date.";
  }

  // Only stop the submission when something is wrong; a valid form is sent
  // to add_notice.php as an ordinary POST request.
  if (problem !== "") {
    event.preventDefault();
    showStatus(formMessage, problem, true);
  }
}

// ---------- Activate / deactivate ----------

function setRowStatus(row, isActive) {
  row.setAttribute("data-active", isActive ? "1" : "0");
  row.classList.toggle("row-inactive", !isActive);

  let pill = row.querySelector(".status-pill");
  pill.textContent = isActive ? "Active" : "Inactive";
  pill.className = "status-pill " + (isActive ? "status-active" : "status-inactive");

  row.querySelector(".toggle-button").textContent = isActive ? "Deactivate" : "Activate";
}

function toggleNotice() {
  let button = this;
  let noticeId = Number(button.getAttribute("data-notice-id"));
  let row = document.getElementById("row-" + noticeId);
  let makeActive = row.getAttribute("data-active") !== "1";

  button.disabled = true;
  showStatus(manageMessage, makeActive ? "Activating..." : "Deactivating...", false);

  postJson("api/toggle_notice.php", { noticeId: noticeId, active: makeActive })
    .then(function(data) {
      setRowStatus(row, data.active);
      showStatus(manageMessage, data.message, false);
      applyStatusFilter();
    })
    .catch(function(error) {
      showStatus(manageMessage, "Could not change the notice: " + error.message, true);
    })
    .finally(function() {
      button.disabled = false;
    });
}

// Shows all rows, only active rows or only inactive rows (no server request needed).
function applyStatusFilter() {
  let choice = $("#status-filter").val();

  $(".notice-row").each(function() {
    let isActive = $(this).attr("data-active") === "1";

    if (choice === "all" || (choice === "active" && isActive) || (choice === "inactive" && !isActive)) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}

noticeForm.addEventListener("submit", validateNoticeForm);
titleInput.addEventListener("input", showTitleCount);
dueDateInput.addEventListener("change", dueDateChanged);
$(".toggle-button").on("click", toggleNotice);
$("#status-filter").on("change", applyStatusFilter);

showTitleCount();
