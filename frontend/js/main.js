// ======== GENERAL ========
var draggedElement;
var draggedElementBeginIndex;
function obj(id) {
	return document.getElementById(id);
}
function getChildIndex(node) {
	return Array.prototype.indexOf.call(node.parentNode.childNodes, node);
}
function getCheckedRadioValue(name) {
	// return the LAST checked element (so we can define default via hidden elements)
	var found = null;
	var inputs = document.getElementsByName(name);
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].checked) {
			found = inputs[i].value;
		}
	}
	return found;
}
function setCheckedRadioValue(name, value) {
	var inputs = document.getElementsByName(name);
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].value == value) {
			inputs[i].checked = true;
		}
	}
}
function toggleSidebar(force=null) {
	if(force == null) {
		obj('explorer').classList.toggle('nosidebar');
		return;
	}
	if(window.innerWidth < 750 /*as defined in CSS*/) {
		if(!force)
			obj('explorer').classList.add('nosidebar');
		else
			obj('explorer').classList.remove('nosidebar');
		return;
	}
}
function toggleTextBoxMultiLine(element) {
	var newTagName = 'input';
	if(element.tagName.toLowerCase() == 'input') newTagName = 'textarea';
	var newElement = document.createElement(newTagName);
	newElement.id = element.id;
	newElement.classList = element.classList;
	newElement.value = element.value;
	newElement.placeholder = element.placeholder;
	element.replaceWith(newElement);
}
function toggleInputDirectory(element) {
	if(element.getAttribute('webkitdirectory') != 'true') {
		element.setAttribute('webkitdirectory', true);
		element.setAttribute('directory', true);
	} else {
		element.removeAttribute('webkitdirectory');
		element.removeAttribute('directory');
	}
}

function rewriteUrlContentParameter(paramsToReplace={}, refresh=false) {
	// compile parameters to replace from ajax request URL
	var url = new URL(currentExplorerContentUrl, location);
	paramsToReplace['view'] = url.pathname.split(/[\\/]/).pop().split('.')[0];
	// replace the params in current URL
	var parameters = [];
	for(const [key, value] of url.searchParams) {
		if(key in paramsToReplace) {
			if(paramsToReplace[key] !== null) {
				parameters[key] = paramsToReplace[key];
			}
		} else {
			parameters[key] = value;
		}
	}
	// add missing additional params
	Object.keys(paramsToReplace).forEach(function(key) {
		if(!(key in parameters)) {
			if(paramsToReplace[key] !== null) {
				parameters[key] = paramsToReplace[key];
			}
		}
	});
	// add new entry to browser history
	var keyValuePairs = [];
	Object.keys(parameters).forEach(function(key) {
		keyValuePairs.push(
			encodeURIComponent(key)+'='+encodeURIComponent(parameters[key])
		);
	});
	currentExplorerContentUrl = url.pathname+'?'+keyValuePairs.join('&');
	window.history.pushState(
		currentExplorerContentUrl,
		document.title,
		document.location.pathname+'?'+keyValuePairs.join('&')
	);
	// reload content with new query parameters
	if(refresh) refreshContent();
}
function getCurrentUrlParameter(param) {
	var url = new URL(location);
	for(const [key, value] of url.searchParams) {
		if(key == param) return value;
	}
}
function openTab(tabControl, tabName, forceRefresh=false) {
	var childs = tabControl.querySelectorAll('.tabbuttons > a, .tabcontents > div');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('name') == tabName) {
			childs[i].classList.add('active');
		} else {
			childs[i].classList.remove('active');
		}
	}
	var childs = tabControl.querySelectorAll('.tabadditionals');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('tab') == tabName) {
			childs[i].classList.remove('hidden');
		} else {
			childs[i].classList.add('hidden');
		}
	}
	let refresh = (forceRefresh && getCurrentUrlParameter('tab') != tabName);
	rewriteUrlContentParameter(currentExplorerContentUrl, {'tab':tabName});
	if(refresh) refreshContent();
}

// ======== EVENT LISTENERS ========
window.onpopstate = function(event) {
	if(event.state != null) {
		// browser's back button pressed
		ajaxRequest(event.state, 'explorer-content', null, false);
	}
};
window.onkeydown = function(event) {
	// F1 - Help
	if((event.which || event.keyCode) == 112) {
		event.preventDefault();
		refreshContentExplorer('views/docs.php');
	}
	// F3 - Search
	if((event.which || event.keyCode) == 114) {
		event.preventDefault();
		txtGlobalSearch.focus();
	}
	// F5 - Reload Explorer Content
	if((event.which || event.keyCode) == 116) {
		event.preventDefault();
		refreshContent();
		refreshSidebar();
	}
};

// ======== DIALOG ========
const DIALOG_BUTTONS_NONE   = 0;
const DIALOG_BUTTONS_RELOAD = 1;
const DIALOG_BUTTONS_CLOSE  = 2;
const DIALOG_SIZE_LARGE     = 0;
const DIALOG_SIZE_SMALL     = 1;
const DIALOG_SIZE_AUTO      = 2;
function showDialog(title='', text='', controls=false, size=false, monospace=false) {
	showDialogHTML(title, escapeHTML(text), controls, size, monospace);
}
function showDialogAjax(title='', url='', controls=false, size=false, callback=null) {
	// show dark background while waiting for response
	obj('dialog-container').classList.add('loading');
	// show loader if request took a little longer (would be annoying if shown directly)
	dialogLoaderTimer = setTimeout(function(){ obj('dialog-container').classList.add('loading2') }, 100);
	// start ajax request
	let finalAction = function() {
		obj('dialog-container').classList.remove('loading');
		obj('dialog-container').classList.remove('loading2');
		clearTimeout(dialogLoaderTimer);
	};
	ajaxRequest(url, null, function(text) {
		showDialogHTML(title, text, controls, size, false);
		if(callback != undefined && typeof callback == 'function') {
			callback(this.responseText);
		}
		finalAction();
	}, false, false, finalAction);
}
function showDialogHTML(title='', text='', controls=false, size=false, monospace=false, loading=false) {
	obj('dialog-title').innerText = title;
	obj('dialog-text').innerHTML = text;
	// buttons
	obj('btnDialogClose').style.visibility = 'collapse';
	if(controls == DIALOG_BUTTONS_CLOSE) {
		obj('btnDialogClose').style.visibility = 'visible';
	}
	// size
	obj('dialog-box').className = '';
	if(size == DIALOG_SIZE_LARGE) {
		obj('dialog-box').classList.add('large');
	} else if(size == DIALOG_SIZE_SMALL) {
		obj('dialog-box').classList.add('small');
	}
	// font
	if(monospace) {
		obj('dialog-text').classList.add('monospace');
	} else {
		obj('dialog-text').classList.remove('monospace');
	}
	// loading animation
	if(loading) {
		var img = document.createElement('img');
		img.src = 'img/loader-dots.svg';
		img.style = 'display:block';
		obj('dialog-text').appendChild(img);
	}
	// make dialog visible
	obj('dialog-container').classList.add('active');
	let animation = obj('dialog-box').animate(
		[ {transform:'scale(102%)'}, {transform:'scale(100%)'} ],
		{ duration: 200, iterations: 1, easing:'ease' }
	);
	// set focus
	animation.onfinish = (event) => {
		var childs = obj('dialog-text').querySelectorAll('*');
		for(var i = 0; i < childs.length; i++) {
			if(childs[i].getAttribute('autofocus')) {
				childs[i].focus();
				break;
			}
		}
	};
}
function hideDialog() {
	let animation = obj('dialog-box').animate(
		[ {transform:'scale(100%)'}, {transform:'scale(98%)'} ],
		{ duration: 100, iterations: 1, easing:'linear' }
	);
	animation.onfinish = (event) => {
		obj('dialog-container').classList.remove('active');
		obj('dialog-title').innerText = '';
		obj('dialog-text').innerHTML = '';
	};
}
function escapeHTML(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

// ======== AJAX OPERATIONS ========
var currentExplorerContentUrl = null;
var lastExplorerTreeContent = '';
function ajaxRequest(url, objID, callback, addToHistory=true, showFullscreenLoader=true, errorCallback=null) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		showLoader(true);
		// show fullscreen loading animation only if query takes longer than 200ms (otherwise annoying)
		if(showFullscreenLoader) timer = setTimeout(showLoader2, 200, true);
	}
	var xhttp = new XMLHttpRequest();
	xhttp.userCancelled = false;
	xhttp.onreadystatechange = function() {
		if(this.readyState != 4) {
			return;
		}
		if(this.status == 200) {
			var object = obj(objID);
			if(object != null) {
				if(objID == 'explorer-tree') {
					// only update content if new content differs to avoid page jumps
					// this info must be stored in a sparate variable since we manipulate classes to restore tree view expanded/collapsed states
					if(lastExplorerTreeContent != this.responseText) {
						object.innerHTML = this.responseText;
						lastExplorerTreeContent = this.responseText;
						initLinks(object);
					}
				} else {
					object.innerHTML = this.responseText;
					if(objID == 'explorer-content') {
						// add to history
						if(addToHistory) rewriteUrlContentParameter();
						// set page title
						let titleObject = obj('page-title');
						if(titleObject != null) document.title = titleObject.innerText;
						else document.title = LANG['project_name'];
						// init newly loaded tables
						initTables(object);
					}
					initLinks(object);
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.status == 401) {
			let currentUrl = new URL(window.location.href);
			window.location.href = 'login.php?redirect='+encodeURIComponent(currentUrl.pathname+currentUrl.search);
		} else {
			if(!this.userCancelled) {
				if(this.status == 0) {
					emitMessage(LANG['no_connection_to_server'], LANG['please_check_network'], MESSAGE_TYPE_ERROR);
				} else {
					emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR);
				}
			}
			if(errorCallback != undefined && typeof errorCallback == 'function') {
				errorCallback(this.responseText);
			}
		}
		// hide loaders
		if(objID == 'explorer-content') {
			if(showFullscreenLoader) clearTimeout(timer);
			showLoader(false);
			showLoader2(false);
		}
	};
	xhttp.open('GET', url, true);
	xhttp.send();
	return xhttp;
}
function ajaxRequestPost(url, body, objID, callback, errorCallback) {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			var object = obj(objID);
			if(object != null) {
				object.innerHTML = this.responseText;
				if(objID == 'explorer-content') {
					initTables(object) // init newly loaded tables
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.readyState == 4) {
			if(errorCallback != undefined && typeof errorCallback == 'function') {
				errorCallback(this.status, this.statusText, this.responseText);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	xhttp.open('POST', url, true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send(body);
	return xhttp;
}
function urlencodeObject(srcjson) {
	if(typeof srcjson !== 'object') return null;
	var urljson = '';
	var keys = Object.keys(srcjson);
	for(var i=0; i <keys.length; i++){
		urljson += encodeURIComponent(keys[i]) + '=' + encodeURIComponent(srcjson[keys[i]]);
		if(i < (keys.length-1)) urljson+='&';
	}
	return urljson;
}
function urlencodeArray(src) {
	if(!Array.isArray(src)) return null;
	var urljson = '';
	for(var i=0; i <src.length; i++){
		urljson += encodeURIComponent(src[i]['key']) + '=' + encodeURIComponent(src[i]['value']);
		if(i < (src.length-1)) urljson+='&';
	}
	return urljson;
}
function initLinks(root) {
	var links = root.querySelectorAll('a');
	for(var i = 0; i < links.length; i++) {
		var linkUrl = links[i].getAttribute('href');
		if(linkUrl == null || !linkUrl.startsWith('index.php?view=')) continue;
		// open explorer-content links via AJAX, do not reload the complete page
		links[i].addEventListener('click', function(e) {
			e.preventDefault();
			toggleAutoRefresh(false);
			var urlParams = new URLSearchParams(this.getAttribute('href').split('?')[1]);
			var ajaxUrlParams = [];
			for(const entry of urlParams.entries()) {
				ajaxUrlParams.push(encodeURIComponent(entry[0])+'='+encodeURIComponent(entry[1]));
			}
			refreshContentExplorer('views/'+encodeURIComponent(urlParams.get('view'))+'.php?'+ajaxUrlParams.join('&'));
		});
	}
}

function showLoader(state) {
	// decent loading indication (loading cursor)
	if(state) document.body.classList.add('loading');
	else document.body.classList.remove('loading');
}
function showLoader2(state) {
	// blocking loading animation (fullscreen loader)
	if(state) {
		explorer.classList.add('diffuse');
		explorer.classList.add('noresponse');
		header.classList.add('progress');
	} else {
		explorer.classList.remove('diffuse');
		explorer.classList.remove('noresponse');
		header.classList.remove('progress');
	}
}

function toggleCheckboxesInContainer(container, checked) {
	let items = container.children;
	for(var i = 0; i < items.length; i++) {
		if(items[i].style.display == 'none') continue;
		let inputs = items[i].getElementsByTagName('input');
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == 'checkbox' && !inputs[n].disabled) {
				inputs[n].checked = checked;
			}
		}
	}
}
function getSelectedCheckBoxValues(checkboxName, attributeName=null, warnIfEmpty=false, root=document) {
	var values = [];
	root.querySelectorAll('input').forEach(function(entry) {
		if(entry.name == checkboxName && entry.checked) {
			if(attributeName == null) {
				values.push(entry.value);
			} else {
				values.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(warnIfEmpty && values.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return values;
}
function getAllCheckBoxValues(checkboxName, attributeName=null, warnIfEmpty=false, root=document) {
	var values = [];
	root.querySelectorAll('input').forEach(function(entry) {
		if(entry.name == checkboxName) {
			if(attributeName == null) {
				values.push(entry.value);
			} else {
				values.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(warnIfEmpty && values.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return values;
}
function getSelectedSelectBoxValues(selectBoxId, warnIfEmpty=false) {
	var selected = [];
	var items = document.getElementById(selectBoxId);
	for(var i = 0; i < items.length; i++) {
		if(items[i].selected) {
			selected.push(items[i].value);
		}
	}
	if(warnIfEmpty && selected.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return selected;
}
function setInputsDisabled(rootElement, disabled) {
	var elements = rootElement.querySelectorAll('input, select, textarea, button');
	for(var i = 0; i < elements.length; i++) {
		elements[i].disabled = disabled;
	}
	elements = rootElement.querySelectorAll('.box');
	for(var i = 0; i < elements.length; i++) {
		if(disabled) elements[i].classList.add('disabled');
		else elements[i].classList.remove('disabled');
	}
}

// ======== CONTENT REFRESH FUNCTIONS ========
const REFRESH_CONTENT_TIMEOUT = 2000;
const STORAGE_KEY_SIDEBAR_STATE = 'sidebar-state';
var refreshContentTimer = null;
var refreshSidebarState = JSON.parse(localStorage.getItem(STORAGE_KEY_SIDEBAR_STATE));
function refreshSidebar(callback=null, handleAutoRefresh=false) {
	// do refresh
	ajaxRequest('views/tree.php', 'explorer-tree', function() {
		// execute custom callback
		if(callback != undefined && typeof callback == 'function') callback(text);
		// register events for expand/collapse
		let setupExpandIcon = function(node) {
			let isExpandable = false;
			let imgs = node.querySelectorAll(':scope > a > img');
			for(let n = 0; n < imgs.length; n++) {
				if(node.classList.contains('expandable')) {
					isExpandable = true;
					imgs[n].title = LANG['expand_or_collapse_tree'];
				}
			}
			return isExpandable;
		}
		let expandOrCollapse = function(e) {
			let node = e.target;
			if(e.target.tagName == 'A') node = e.target.parentElement;
			if(e.target.tagName == 'IMG') node = e.target.parentElement.parentElement;
			node.classList.toggle('expanded');
			if(setupExpandIcon(node)) {
				e.preventDefault();
				e.stopPropagation();
			}
			// save node expand states
			if(refreshSidebarState == null) refreshSidebarState = {};
			let elements = obj('explorer-tree').querySelectorAll('.node, .subnode');
			for(let i = 0; i < elements.length; i++) {
				if(elements[i].id) {
					refreshSidebarState[elements[i].id] = elements[i].classList.contains('expanded');
				}
			}
			localStorage.setItem(STORAGE_KEY_SIDEBAR_STATE, JSON.stringify(refreshSidebarState));
		}
		let elements = obj('explorer-tree').querySelectorAll('.node > a, .subnode > a');
		for(let i = 0; i < elements.length; i++) {
			elements[i].ondblclick = expandOrCollapse;
			elements[i].onkeypress = function(e){
				if(e.code == 'Space') expandOrCollapse(e);
			};
			let children = elements[i].querySelectorAll(':scope > img');
			if(children.length) children[0].onclick = expandOrCollapse;
		}
		// restore previous expand states
		for(let key in refreshSidebarState) {
			if(refreshSidebarState[key]) {
				let node = obj(key);
				if(node) node.classList.add('expanded');
			}
		}
	}, false);
}
function refreshContent(callback=null, handleAutoRefresh=false) {
	if(currentExplorerContentUrl == null) return;
	ajaxRequest(currentExplorerContentUrl, 'explorer-content', function(text) {
		// execute custom callback
		if(callback != undefined && typeof callback == 'function') callback(text);
		// schedule next refresh after loading finished
		if(handleAutoRefresh && refreshContentTimer != null) {
			scheduleNextContentRefresh();
		}
	}, false, !handleAutoRefresh);
}
function scheduleNextContentRefresh() {
	refreshContentTimer = setTimeout(function(){ refreshContent(null, true) }, REFRESH_CONTENT_TIMEOUT);
}
function toggleAutoRefresh(force=null) {
	let newState = (refreshContentTimer == null);
	if(force != null) newState = force;
	if(newState) {
		scheduleNextContentRefresh();
		btnRefresh.classList.add('active');
	} else {
		clearTimeout(refreshContentTimer);
		refreshContentTimer = null;
		btnRefresh.classList.remove('active');
	}
}
function refreshContentExplorer(url, callback=null) {
	ajaxRequest(url, 'explorer-content', callback);
}

// ======== SEARCH OPERATIONS ========
function doSearch(query) {
	ajaxRequest('views/search.php?query='+encodeURIComponent(query), 'search-results');
	openSearchResults();
}
function closeSearchResults() {
	obj('search-results').classList.remove('visible');
	obj('search-glass').classList.remove('focus');
	obj('explorer').classList.remove('diffuse');
}
function openSearchResults() {
	obj('search-results').classList.add('visible');
	obj('search-glass').classList.add('focus');
	obj('explorer').classList.add('diffuse');
}
function handleSearchResultNavigation(event) {
	if(event.code == 'ArrowDown') focusNextSearchResult();
	else if(event.code == 'ArrowUp') focusNextSearchResult(-1);
}
function focusNextSearchResult(step=1) {
	var links = document.querySelectorAll('#search-results a');
	for(let i=0; i<links.length; i++) {
		if(links[i] === document.activeElement) {
			var next = links[i + step] || links[0];
			next.focus();
			return;
		}
	}
	links[0].focus();
}
function checkUpdate() {
	ajaxRequestPost('ajax-handler/update-check.php', '', null, function(text) {
		if(text.trim() != '') {
			emitMessage(LANG['update_available'], text.trim(), MESSAGE_TYPE_INFO);
		}
	});
}

// ======== MESSAGE BOX OPERATIONS ========
const MESSAGE_TYPE_INFO    = 'info';
const MESSAGE_TYPE_SUCCESS = 'success';
const MESSAGE_TYPE_WARNING = 'warning';
const MESSAGE_TYPE_ERROR   = 'error';
function emitMessage(title, text, type='info', timeout=8000) {
	let dismissMessage = function() {
		let animation = messageBox.animate(
			[ {opacity:1, transform:'translateX(0)'}, {opacity:0, transform:'translateX(80%)'} ],
			{ duration: 400, iterations: 1, easing:'ease' }
		);
		animation.onfinish = (event) => {
			messageBox.remove();
		};
	};
	var messageBox = document.createElement('div');
	messageBox.classList.add('message');
	messageBox.classList.add('icon');
	messageBox.classList.add(type);
	var messageBoxContent = document.createElement('div');
	messageBoxContent.classList.add('message-content');
	messageBox.appendChild(messageBoxContent);
	var messageBoxTitle = document.createElement('div');
	messageBoxTitle.classList.add('message-title');
	messageBoxTitle.innerText = title;
	messageBoxContent.appendChild(messageBoxTitle);
	var messageBoxText = document.createElement('div');
	messageBoxText.innerText = text;
	messageBoxContent.appendChild(messageBoxText);
	var messageBoxClose = document.createElement('button');
	messageBoxClose.classList.add('message-close');
	messageBoxClose.innerText = 'Close';
	messageBoxClose.onclick = dismissMessage;
	messageBox.appendChild(messageBoxClose);
	obj('message-container').prepend(messageBox);
	if(timeout != null) setTimeout(dismissMessage, timeout);
}

// ======== OBJECT OPERATIONS ========
function createObject(typeId) {
	ajaxRequestPost('ajax-handler/object.php',
		urlencodeObject({'create_object_of_type':typeId}),
		null,
		function(text) {
			refreshContentExplorer('views/object.php?id='+encodeURIComponent(text), function() {
				editMode(obj('explorer-content').querySelectorAll('div.category')[0]);
			});
		}
	);
}
function editMode(container) {
	container.classList.remove('template');
	container.querySelectorAll('table.category')[0].classList.add('edit');
	container.querySelectorAll('button.edit')[0].classList.add('hidden');
	container.querySelectorAll('button.clear')[0].classList.add('hidden');
	container.querySelectorAll('button.save')[0].classList.remove('hidden');
	container.querySelectorAll('button.cancel')[0].classList.remove('hidden');
	let fields = container.querySelectorAll('table.category input, table.category select, table.category textarea');
	if(fields.length) {
		fields[0].focus();
	}
}
function viewMode(container) {
	container.querySelectorAll('table.category')[0].classList.remove('edit');
	container.querySelectorAll('button.edit')[0].classList.remove('hidden');
	container.querySelectorAll('button.clear')[0].classList.remove('hidden');
	container.querySelectorAll('button.save')[0].classList.add('hidden');
	container.querySelectorAll('button.cancel')[0].classList.add('hidden');
}
function saveCategory(objectId, container) {
	let postdata = {'edit_id':objectId};
	let fields = container.querySelectorAll('table.category input, table.category select, table.category textarea');
	for(let i=0; i<fields.length; i++) {
		postdata[fields[i].name] = fields[i].value;
	}
	ajaxRequestPost('ajax-handler/object.php',
		urlencodeObject(postdata),
		null,
		function(text) {
			emitMessage(LANG['saved'], text.trim(), MESSAGE_TYPE_SUCCESS);
			refreshContent();
		}
	);
}
function confirmDeleteCategorySet(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_category_set_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm( LANG['confirm_remove_categories'].replace('%1',ids.length))) {
		ajaxRequestPost('ajax-handler/object.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['categories_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemoveObjects(ids, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm( LANG['confirm_remove_objects'].replace('%1',ids.length))) {
		ajaxRequestPost('ajax-handler/object.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['objects_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
