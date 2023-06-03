<meta name="color-scheme" content="light dark"> 
<div class="button-container">
    <button style="width:8%" onclick="sendGetRequest('pull')">Pull</button>
    <button style="width:8%" onclick="sendGetRequest('reset')">Reset</button>
    <button style="width:8%" onclick="sendGetRequest('update')">Update</button>
    <button style="width:8%" onclick="sendGetRequest('restart')">Restart</button>
    <button style="background-color: black; color:white; display:flex; justify-content:center; align-items:center; height:100%; width:68%; flex-grow: 1;" onclick="window.open('<?= $TOTK->github ?>')"><?= $TOTK->discord->user->displayname ?></button>
</div>
<div class="alert-container"></div>
<div class="checkpoint"><?= str_replace('[' . date("Y"), '</div><div> [' . date("Y"), 
        str_replace([PHP_EOL, '[] []', ' [] '], '</div><div>', $return)
    ); ?>
</div>
<div class='reload-container'>
    <button onclick='location.reload()'>Reload</button>
</div>
<div class='loading-container'>
    <div class='loading-bar'></div>
</div>
<script>
    var mainScrollArea=document.getElementsByClassName('checkpoint')[0];
    var scrollTimeout;
    window.onload=function(){
        if(window.location.href==localStorage.getItem('lastUrl')){
            mainScrollArea.scrollTop=localStorage.getItem('scrollTop');
        }else{
            localStorage.setItem('lastUrl',window.location.href);
            localStorage.setItem('scrollTop',0);
        }
    };
    mainScrollArea.addEventListener('scroll',function(){
        clearTimeout(scrollTimeout);
        scrollTimeout=setTimeout(function(){
            localStorage.setItem('scrollTop',mainScrollArea.scrollTop);
        },100);
    });
    function sendGetRequest(endpoint) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.protocol + '//' + window.location.hostname + ':<?= $port ?>/' + endpoint, true);
        xhr.onload = function() {
            var response = xhr.responseText.replace(/(<([^>]+)>)/gi, '');
            var alertContainer = document.querySelector('.alert-container');
            var alert = document.createElement('div');
            alert.innerHTML = response;
            alertContainer.appendChild(alert);
            setTimeout(function() {
                alert.remove();
            }, 15000);
            if (endpoint === 'restart') {
                var loadingBar = document.querySelector('.loading-bar');
                var loadingContainer = document.querySelector('.loading-container');
                loadingContainer.style.display = 'block';
                var width = 0;
                var interval = setInterval(function() {
                    if (width >= 100) {
                        clearInterval(interval);
                        location.reload();
                    } else {
                        width += 2;
                        loadingBar.style.width = width + '%';
                    }
                }, 300);
                loadingBar.style.backgroundColor = 'white';
                loadingBar.style.height = '20px';
                loadingBar.style.position = 'fixed';
                loadingBar.style.top = '50%';
                loadingBar.style.left = '50%';
                loadingBar.style.transform = 'translate(-50%, -50%)';
                loadingBar.style.zIndex = '9999';
                loadingBar.style.borderRadius = '5px';
                loadingBar.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)';
                var backdrop = document.createElement('div');
                backdrop.style.position = 'fixed';
                backdrop.style.top = '0';
                backdrop.style.left = '0';
                backdrop.style.width = '100%';
                backdrop.style.height = '100%';
                backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                backdrop.style.zIndex = '9998';
                document.body.appendChild(backdrop);
                setTimeout(function() {
                    clearInterval(interval);
                    if (!document.readyState || document.readyState === 'complete') {
                        location.reload();
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    }
                }, 5000);
            }
        };
        xhr.send();
    }
</script>
<style>
    .button-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: #f1f1f1;
        overflow: hidden;
    }
    .button-container button {
        float: left;
        display: block;
        color: black;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        font-size: 17px;
        border: none;
        cursor: pointer;
        color: white;
        background-color: black;
    }
    .button-container button:hover {
        background-color: #ddd;
    }
    .checkpoint {
        margin-top: 100px;
    }
    .alert-container {
        position: fixed;
        top: 0;
        right: 0;
        width: 300px;
        height: 100%;
        overflow-y: scroll;
        padding: 20px;
        color: black;
        background-color: black;
    }
    .alert-container div {
        margin-bottom: 10px;
        padding: 10px;
        background-color: #fff;
        border: 1px solid #ddd;
    }
    .reload-container {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 20px;
    }
    .reload-container button {
        display: block;
        color: black;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        font-size: 17px;
        border: none;
        cursor: pointer;
    }
    .reload-container button:hover {
        background-color: #ddd;
    }
    .loading-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
    }
    .loading-bar {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 0%;
        height: 20px;
        background-color: white;
    }
    .nav-container {
        position: fixed;
        bottom: 0;
        right: 0;
        margin-bottom: 20px;
    }
    .nav-container button {
        display: block;
        color: black;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        font-size: 17px;
        border: none;
        cursor: pointer;
        color: white;
        background-color: black;
        margin-right: 10px;
    }
    .nav-container button:hover {
        background-color: #ddd;
    }
    .checkbox-container {
        display: inline-block;
        margin-right: 10px;
    }
    .checkbox-container input[type=checkbox] {
        display: none;
    }
    .checkbox-container label {
        display: inline-block;
        background-color: #ddd;
        padding: 5px 10px;
        cursor: pointer;
    }
    .checkbox-container input[type=checkbox]:checked + label {
        background-color: #bbb;
    }
</style>
<div class='nav-container'>
    <?php if ($sub == 'botlog' ): ?>
        <button onclick="location.href='/botlog2'">Botlog 2</button>
    <?php else: ?>
        <button onclick="location.href='/botlog'">Botlog 1</button>
    <?php endif; ?>
</div>
<div class='reload-container'>
    <div class='checkbox-container'>
        <input type='checkbox' id='auto-reload-checkbox' <?= (isset($_COOKIE['auto-reload']) && $_COOKIE['auto-reload'] == 'true' ? 'checked' : '') ?>>
        <label for='auto-reload-checkbox'>Auto Reload</label>
    </div>
    <button id='reload-button'>Reload</button>
</div>
<script>
    var reloadButton = document.getElementById('reload-button');
    var autoReloadCheckbox = document.getElementById('auto-reload-checkbox');
    var interval;

    reloadButton.addEventListener('click', function() {
        clearInterval(interval);
        location.reload();
    });

    autoReloadCheckbox.addEventListener('change', function() {
        if (this.checked) {
            interval = setInterval(function() {
                location.reload();
            }, 15000);
            localStorage.setItem('auto-reload', 'true');
        } else {
            clearInterval(interval);
            localStorage.setItem('auto-reload', 'false');
        }
    });

    if (localStorage.getItem('auto-reload') == 'true') {
        autoReloadCheckbox.checked = true;
        interval = setInterval(function() {
            location.reload();
        }, 15000);
    }
</script>