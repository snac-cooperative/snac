/**
 * SNAC Session Management
 *
 * Session management code, that when included, alerts the user when their session
 * is about to expire and gives them the option to extend their session in 1-hour
 * chunks.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2018 the Rector and Visitors of the University of Virginia
 */


var sessionExpirationTime = null;  //this is in seconds

function sessionHandleExtend(data) {
    console.log(data);
    if (data.result && data.result == 'success') {
        bootbox.alert("Successfully extended session");
        sessionExpirationTime = data.user.token.expires;
    } else
        bootbox.alert("An error occurred while extending your session.  Please save and log out.");
}

function sessionAlertExpired() {
    bootbox.alert("Your session has expired. Please log out and back in");
}

function alertContinueSession(left) {
    var mins = Math.floor(left);
    bootbox.confirm({
        title: "Session Expiring",
        message: function() {
            var message = "<p>Your log-in session is set to expire in " + mins + " minutes.  Do you want to continue with your session?</p>";
            return message;
        },
        buttons: {
            cancel: {
                label: '<i class="fa fa-times"></i> Ignore'
            },
            confirm: {
                label: '<i class="fa fa-check"></i> Continue Session'
            }
        },
        callback: function (result) {
            if (result) {
                $.get(snacUrl+"/extend", null, sessionHandleExtend);
            }
        }
    });

}

function startSessionManager(expires) {
    sessionExpirationTime = expires;
    var now = new Date().getTime() / 1000;
    if (now >= sessionExpirationTime) {
        console.log("User should have been expired");
    } else {
        // watch for expiration
        var interval = setInterval(function() {
            var minsLeft = (sessionExpirationTime - (new Date().getTime() / 1000)) / 60;
            if (minsLeft <= 0) {
                console.log("Time has expired on session");
                sessionAlertExpired();
            } else if (minsLeft < 8) {
                console.log("Last warning on session");
                alertContinueSession(minsLeft);
            } else if (minsLeft < 13) {
                console.log("Should begin warning on session, only one more wakeup time left");
                alertContinueSession(minsLeft);
            } 
        }, 1000*60*5); // milliseconds, so 5 minutes

        // when getting close, make an AJAX query to the server with a session extend call.  That
        // may give us a way to update the session variable and keep the session open.  The server
        // already has a sessionExtend method in DBUser that is called from ServerExecutor's
        // authenticateUser method.  We could pop up a modal saying "your session is going to expire"
        // with options to stay logged in or log out and return home.  When the session has expired
        // we should let them know by a modal, and give them the option to stay here (knowing nothing
        // will work)
    }
}
