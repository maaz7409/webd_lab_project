// Notice board: marks notices as read or acknowledged without reloading the page.

let boardMessage = document.getElementById("board-message");
let filterForm = document.getElementById("filter-form");

// Writes the counts returned by the server into the summary tiles.
function updateSummary(summary) {
  $("#summary-total").text(summary.total);
  $("#summary-unread").text(summary.unread);
  $("#summary-pending").text(summary.pending);
  $("#summary-overdue").text(summary.overdue);
}

// Changes one notice card to match the state returned by the server.
function applyState(card, data) {
  let stateLabel = card.querySelector(".state");
  stateLabel.textContent = data.stateText;
  stateLabel.className = "state " + data.stateClass;

  if (data.isRead) {
    card.classList.remove("is-unread");
    $(card).find(".mark-read-button").remove();
  }

  if (data.isAcknowledged) {
    $(card).find(".ack-button").remove();
    $(card).find(".due-date").removeClass("due-soon due-overdue due-later").addClass("due-done");
  }
}

function updateNoticeStatus() {
  let button = this;
  let noticeId = Number(button.getAttribute("data-notice-id"));
  let action = button.getAttribute("data-action");
  let card = document.getElementById("notice-" + noticeId);
  let cardButtons = $(card).find("button");

  // Prevent a second click from sending a competing request.
  cardButtons.prop("disabled", true);
  showStatus(boardMessage, "Saving...", false);

  postJson("api/update_status.php", { noticeId: noticeId, action: action })
    .then(function(data) {
      applyState(card, data);
      updateSummary(data.summary);
      showStatus(boardMessage, data.message, false);
    })
    .catch(function(error) {
      showStatus(boardMessage, "Could not update the notice: " + error.message, true);
    })
    .finally(function() {
      cardButtons.prop("disabled", false);
    });
}

// Choosing a different category or view applies the filter straight away.
function submitFilters() {
  filterForm.submit();
}

$(".status-button").on("click", updateNoticeStatus);
document.getElementById("category").addEventListener("change", submitFilters);
document.getElementById("view").addEventListener("change", submitFilters);
