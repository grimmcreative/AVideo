<?php
// this script will be executed on the AVideo side
$meetPlugin = AVideoPlugin::getDataObjectIfEnabled("Meet");
if (empty($meetPlugin)) {
    return false;
}


$livePlugin = AVideoPlugin::getDataObjectIfEnabled("Live");
if (!empty($livePlugin) && User::canStream()) {
    $trasnmition = LiveTransmition::createTransmitionIfNeed(User::getId());
    $dropURL = "{$global['webSiteRootURL']}plugin/Live/droplive.json.php?live_transmition_id={$trasnmition['id']}&live_servers_id=" . Live::getCurrentLiveServersId();
    $rtmpLink = Live::getRTMPLink();
}
?>
<script src="<?php echo $global['webSiteRootURL']; ?>plugin/Meet/external_api.js" type="text/javascript"></script>
<script>
    var lastLiveStatus;
    var eventMethod = window.addEventListener
            ? "addEventListener"
            : "attachEvent";
    var eventer = window[eventMethod];
    var messageEvent = eventMethod === "attachEvent"
            ? "onmessage"
            : "message";
    eventer(messageEvent, function (e) {
        if (typeof e.data.isLive !== 'undefined') {
            if (lastLiveStatus !== e.data.isLive) {
                lastLiveStatus = e.data.isLive;
                console.log("YPTMeetScript live status changed");
                if (lastLiveStatus) {
                    if (typeof event_on_live !== "undefined") {
                        event_on_live();
                    }
                } else {
                    if (typeof event_on_liveStop !== "undefined") {
                        event_on_liveStop();
                    }
                }
                if (typeof event_on_liveStatusChange !== "undefined") {
                    event_on_liveStatusChange();
                }
            }
        } else if (typeof e.data.YPTisReady !== 'undefined') {
            if (typeof event_on_meetReady !== "undefined") {
                event_on_meetReady();
            }
            console.log("YPTMeetScript is loaded");
        } else if (typeof e.data.conferenceIsReady !== 'undefined') {
            if (typeof event_on_meetReady !== "undefined") {
                event_on_meetReady();
            }
            console.log("YPTMeetScript conference is ready");
        }
    });
    var api;
    function aVideoMeetStart(domain, roomName, jwt, email, displayName, TOOLBAR_BUTTONS) {
        const options = {
            roomName: roomName,
            jwt: jwt,
            parentNode: document.querySelector('#meet'),
            userInfo: {
                email: email,
                displayName: displayName
            },
            interfaceConfigOverwrite: {
                TOOLBAR_BUTTONS: TOOLBAR_BUTTONS,
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                disableAudioLevels: true,
                requireDisplayName: true,
                enableLayerSuspension: true,
                channelLastN: 4,
                startVideoMuted: 10,
                startAudioMuted: 10,
            }

        };
        api = new JitsiMeetExternalAPI(domain, options);

        if (typeof readyToClose !== "undefined") {
            api.addEventListeners({
                readyToClose: readyToClose,
            });
        }

    }

    function aVideoMeetStartRecording(RTMPLink, dropURL) {
        on_processingLive();
        if (dropURL) {
            $.ajax({
                url: dropURL,
                success: function (response) {
                    console.log("YPTMeetScript Start Recording Drop");
                    console.log(response);
                }
            }).always(function (dataOrjqXHR, textStatus, jqXHRorErrorThrown) {
                api.executeCommand('startRecording', {
                    mode: 'stream',
                    youtubeStreamKey: RTMPLink,
                });
            });
        } else {
            api.executeCommand('startRecording', {
                mode: 'stream',
                youtubeStreamKey: RTMPLink,
            });
        }
    }

    function aVideoMeetStopRecording(dropURL) {
        on_processingLive();
        api.executeCommand('stopRecording', 'stream');
        if (dropURL) {
            setTimeout(function () { // if I run the drop on the same time, the stopRecording fails
                $.ajax({
                    url: dropURL,
                    success: function (response) {
                        console.log("YPTMeetScript Stop Recording Drop");
                        console.log(response);
                    }
                });
            }, 5000);
        }

    }

    function aVideoMeetConferenceIsReady() {

    }

    function aVideoMeetHideElement(selectors) {
        document.querySelector("iframe").contentWindow.postMessage({hideElement: selectors}, "*");
    }

    function aVideoMeetAppendElement(parentSelector, html) {
        var append = {parentSelector: parentSelector, html: html};
        document.querySelector("iframe").contentWindow.postMessage({append: append}, "*");
    }

    function aVideoMeetPrependElement(parentSelector, html) {
        var prepend = {parentSelector: parentSelector, html: html};
        document.querySelector("iframe").contentWindow.postMessage({prepend: prepend}, "*");
    }

    function aVideoMeetCreateButtons() {
<?php
if (!empty($rtmpLink)) {
    ?>
            aVideoMeetAppendElement(".button-group-center", <?php echo json_encode(Meet::createJitsiButton(__("Go Live"),"startLive.svg", "alert(1)")); ?>);
            //aVideoMeetAppendElement(".button-group-center", <?php //echo json_encode(Meet::createJitsiButton("startLive.svg", "aVideoMeetStartRecording('$rtmpLink','$dropURL')")); ?>);
            //aVideoMeetAppendElement(".button-group-center", <?php //echo json_encode(Meet::createJitsiButton("stopLive.svg", "aVideoMeetStopRecording('$dropURL')")); ?>);
    <?php
}
?>


    }
</script>