<html>
<body>
<script>
var scheme="{{ $scheme }}";
var universal="{{ $universal }}";
var play="{{ $play }}";
var ios="{{ $ios }}";

function openApp(){
  window.location = scheme;
  setTimeout(()=>window.location = universal,800);
  setTimeout(()=>{
    if(/android/i.test(navigator.userAgent)) window.location=play;
    else window.location=ios;
  },1500);
}
openApp();
</script>
Redirecting...
</body>
</html>
