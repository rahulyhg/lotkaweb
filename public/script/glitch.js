/******
 GLITCH
******/
function Glitch(obj, c) {
    window.requestAnimFrame = (function(){
    return  window.requestAnimationFrame       ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame    ||
            function( callback ){
              window.setTimeout(callback, 1000 / 60);
            };
    })();

    var jpgHeaderLength, imgDataArr, cx, ctx, 
        base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
        base64Map = base64Chars.split(""),
        reverseBase64Map = {},
        img = new Image(),
        strength = 2, 
        that = this;
          
    window.globalGlitchAnimationId = false;
    
    base64Map.forEach(function(val, key) { reverseBase64Map[val] = key; } );

    function init(to_glitch) {
        if (!to_glitch) return that;
      
        if (to_glitch instanceof HTMLImageElement) {
          if (!cx) {
            cx = document.createElement('canvas');
          }
          cx.setAttribute('height', to_glitch.height);
          cx.setAttribute('width', to_glitch.width);
          ctx = cx.getContext('2d');            
          ctx.drawImage(to_glitch,0,0);
        } else {
          cx = to_glitch;
          ctx = cx.getContext('2d');          
        }
      
        cancelAnimationFrame(globalGlitchAnimationId);
        imgDataArr = false;
        return that;
    }
  
    function detectJpegHeaderSize(data) {
      jpgHeaderLength = 417;
      for (var i = 0, l = data.length; i < l; i+=1) {
          if (data[i] == 0xFF && data[i+1] == 0xDA) {
              //console.log("xxxxxxx<<<<", data[i], data[i+1], i, l);
              jpgHeaderLength = i + 2; return;
          }
      }
    }

    // base64 is 2^6, byte is 2^8, every 4 base64 values create three bytes
    function base64ToByteArray(str) {
      var result = [], digitNum, cur, prev;
      for (var i = 23, l = str.length; i < l; i+=1) {
          cur = reverseBase64Map[str.charAt(i)];
          digitNum = (i-23) % 4;
          switch(digitNum){
                  //case 0: first digit - do nothing, not enough info to work with
              case 1: //second digit
                  result.push(prev << 2 | cur >> 4);
                  break;
              case 2: //third digit
                  result.push((prev & 0x0f) << 4 | cur >> 2);
                  break;
              case 3: //fourth digit
                  result.push((prev & 3) << 6 | cur);
                  break;
          }
          prev = cur;
      }
      return result;
    }

    function byteArrayToBase64(arr) {
      var result = ["data:image/png;base64,"], byteNum, cur, prev;
      for (var i = 0, l = arr.length; i < l; i+=1) {
          cur = arr[i];
          byteNum = i % 3;
          switch (byteNum) {
              case 0: //first byte
                  result.push(base64Map[cur >> 2]);
                  break;
              case 1: //second byte
                  result.push(base64Map[(prev & 3) << 4 | (cur >> 4)]);
                  break;
              case 2: //third byte
                  result.push(base64Map[(prev & 0x0f) << 2 | (cur >> 6)]);
                  result.push(base64Map[cur & 0x3f]);
                  break;
          }
          prev = cur;
      }
      if (byteNum === 0) {
          result.push(base64Map[(prev & 3) << 4]);
          result.push("==");
      } else if (byteNum == 1) {
          result.push(base64Map[(prev & 0x0f) << 2]);
          result.push("=");
      }
      return result.join("");
    }

    function glitchJpegBytes(strArr) {
      var rnd = Math.floor(jpgHeaderLength + Math.random() * (strArr.length - jpgHeaderLength - 4));
      strArr[rnd] = Math.floor(Math.random() * 256);
    }

    function glitchJpeg(amnt) {
        var glitchCopy = imgDataArr.slice();
        for (var i = 0; i < amnt; i += 1) {
            glitchJpegBytes(glitchCopy);
        }
        return byteArrayToBase64(glitchCopy);
    }
    
    function glitch(str) {
        strength = str || strength;
        imgDataArr = imgDataArr || base64ToByteArray(cx.toDataURL("image/jpeg"));
        detectJpegHeaderSize(imgDataArr);
        img.src = glitchJpeg(strength);
        return that;
    }    

    function animloop() {
        globalGlitchAnimationId = window.requestAnimationFrame(animloop);        
        glitch();
    }

    function reset() {
        img.src = byteArrayToBase64(imgDataArr)
        return that;
    }
    
    img.onload = function () {
        that.img = img;
        if(c && typeof c === "function") c.call(that, img);
    };
    
    this.anim = function (str) { strength = str; animloop(); return that; };
    this.stop = function () { cancelAnimationFrame(globalGlitchAnimationId); return that; };
    this.setStrength = function (str) { strength = str; return that; };
    this.update = init;
    this.ones = glitch;
    this.reset = reset;
    this.img = []._;
    
    return init(obj);
}