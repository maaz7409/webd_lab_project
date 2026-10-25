// Shared by board.js and manage.js.

// The token printed by header.php; the server rejects changes without it.
let csrfToken = $("#csrf-token").val();

// Sends a JavaScript object to a PHP endpoint as JSON with fetch and returns a
// Promise for the server's JSON reply. Our endpoints always reply with JSON
// containing "ok" and "message", even when they refuse a request, so the
// reply is read first and the message is used as the error text.
function postJson(url, payload) {
  payload.token = csrfToken;

  return fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  })
    .then(function(response) {
      return response.json()
        .catch(function() {
          throw new Error("The server sent an unexpected reply (HTTP " + response.status + ").");
        })
        .then(function(data) {
          if (!response.ok || !data.ok) {
            throw new Error(data.message || "Request failed (HTTP " + response.status + ").");
          }
          return data;
        });
    });
}

// Shows a message in a status paragraph; isError switches its colour.
function showStatus(element, text, isError) {
  element.textContent = text;
  element.classList.toggle("is-error", isError);
}
