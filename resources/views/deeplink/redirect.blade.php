<html>
<body>
<script>
var scheme="{{ $scheme }}";
var universal="{{ $universal }}";
var play="{{ $play }}";
var ios="{{ $ios }}";

function openApp(){
  // Try custom scheme first
  window.location = scheme;
  
  // Try universal link after 1.5s (give more time)
  setTimeout(() => window.location = universal, 1500);
  
  // Fallback to store after 2.5s
  setTimeout(() => {
    if(/android/i.test(navigator.userAgent)) window.location = play;
    else window.location = ios;
  }, 2500);
}
openApp();
</script>
Redirecting...
</body>
</html>
