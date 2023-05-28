// public/js/zego_uikit.js

const roomID = '123';
const userID = Math.floor(Math.random() * 10000) + "";
const userName = "userName" + userID;

fetch('/generate-kit-token', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        roomID: roomID,
        userID: userID,
        userName: userName
    })
})
.then(response => response.json())
.then(({ kitToken }) => {
    const zp = ZegoUIKitPrebuilt.create(kitToken);

    zp.joinRoom({
        container: document.querySelector("#root"),
        sharedLinks: [{
            url: window.location.protocol + '//' + window.location.host + window.location.pathname + '?roomID=' + roomID,
        }],
        scenario: {
           mode: ZegoUIKitPrebuilt.GroupCall,
        },
    });
});
