<!-- resources/views/zego_uikit.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #root {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>
<body>
    <div id="root">Hello</div>

    <script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
    <script src="{{-- asset('js/zego_uikit.js') --}}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>

        // public/js/zego_uikit.js

const roomID = '123';
const userID = Math.floor(Math.random() * 10000) + "";
const userName = "userName" + userID;

$.ajax({
    url: '{{ route("front.generate-kit-token") }}',
    method: 'get',
    dataType: 'json',
    data: {
        roomID: roomID,
        userID: userID,
        userName: userName,
        _token: $('meta[name="csrf-token"]').attr('content')
    },
    success: function (data) {
        const kitToken = data.kitToken;
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
    },
    error: function (error) {
        console.log('Error:', error);
    }
});


    </script>
</body>
</html>
