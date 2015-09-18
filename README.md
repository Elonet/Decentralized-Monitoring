<h1>Decentralized Monitoring</h1>
<h2>Target</h2>
<p>Utiliser les PC du réseau, utilisant une application chrome ou une extension firefox , comme noeud de supervision du réseau, et fournir un aperçu temps réel de ces problématiques :</p>
<ul>
   <li>temps de réponses et d'accès à des services TCP ou HTTP/HTTPS anormalement long</li>
   <li>indisponibilité TCP ponctuelle ou durable.</li>
   <li>indisponibilité HTTP/S ponctuelle ou durable (waiting time socket TCP du GET HTTP/S ou temps d'attente du GET HTTP/S)</li>
   <li>non présence d'une chaîne de caractères par check ASCII sur GET HTTP/HTTPS sur url complète disponibles sans inputs (only GET, pas de POST ou authentification/cookie etc)</li>
</ul>
<br/>
<p>L'aperçu temps réel est fournie de deux manières :</p>
<ul>
   <li>Les alertes email sur événement</li>
   <li>L'écran de visuel sur événement</li>
</ul>
<br/>
<p>On redirige comme avec un proxy, les requêtes de supervision via ces PC à partir du moment où ils ont l'application Chrome ou l'extension firefox, permettant de lancer en background des checks. Les PC deviennent alors des capteurs de surveillance.</p>
<h2>Requirements</h2>
<h5>Mandatory requirements</h5>
<ul>
   <li>Web server (Debian, CentOs, Ubuntu)</li>
   <li>Database (SQL)</li>
   <li>Logstalgia server (Ubuntu)</li>
</ul>
<p>L'ensemble de la solution peut être également installé sur le même serveur, dans ce cas le serveur doit obligatoirement être sous Ubuntu.</p>
<h5>Optional requirements</h5>
<ul>
   <li>Screen for the logstalgia server video</li>
   <li>VNC server for the screensaver</li>
</ul>
<h2>Setup</h2>
<ul>
   <li><a href="https://elonet.fr/tech/doku.php?id=installation_web_server" target="_blank">How to setup the Distribution and correlation service on your website</a></li>
   <li><a href="https://elonet.fr/tech/doku.php?id=installation_logstalgia" target="_blank">How to setup the Visual service on your ubuntu server</a></li>
   <li><a href="https://elonet.fr/tech/doku.php?id=installation_video_server" target="_blank">How to setup the Movie service on your ubuntu server</a></li>
   <li><a href="https://elonet.fr/tech/doku.php?id=installation_screensaver" target="_blank">How to setup the Screensaver service on your ubuntu server</a></li>
   <li><a href="https://elonet.fr/tech/doku.php?id=installation_browser_plugins" target="_blank">How to setup the Browser plugins on your ubuntu server</a></li>
</ul>

<h2>Browsers</h2>
<h5>Desktop browsers</h5>
<p>The Browser plugin is regularly tested with the latest browser versions and supports the following minimal versions:</p>
<ul>
   <li>Google Chrome</li>
   <li>Mozilla Firefox 3.0+</li>
</ul>

<h2>Contributing</h2>
<p><strong>Bug fixes</strong> and <strong>new features</strong> can be proposed using <a href="https://github.com/Elonet/Decentralized-Monitoring/pulls" target="_blank">pull requests</a>.
   Please read the <a href="https://github.com/Elonet/Decentralized-Monitoring/blob/master/CONTRIBUTING.md" target="_blank">contribution guidelines</a> before submitting a pull request.
</p>
<h2>Support</h2>
<p>This project is actively maintained, but there is no official support channel.<br>
   If you have a question that another developer might help you with, please post to <a href="http://stackoverflow.com/questions/tagged/Elonet+Decentralized+Monitoring" target="_blank">Stack Overflow</a> and tag your question with <code>Elonet Decentralized Monitoring</code>.
</p>
<h2>License</h2>
<p>Released under the <a href="http://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank">GPLv3 license</a>.</p>
