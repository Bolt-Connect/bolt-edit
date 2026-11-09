( function () {
	'use strict';

	const fields = Array.from( document.querySelectorAll( '[data-bolt-edit-field]' ) );
	const managedRegions = Array.from( document.querySelectorAll( '[data-bolt-edit-managed]' ) );
	const toolbar = document.querySelector( '[data-bolt-edit-toolbar]' );
	const toggle = document.querySelector( '[data-bolt-edit-toggle]' );
	const status = document.querySelector( '[data-bolt-edit-status]' );
	const highlight = document.querySelector( '[data-bolt-edit-highlight]' );
	const popup = document.querySelector( '[data-bolt-edit-popup]' );
	const popupLabel = document.querySelector( '[data-bolt-edit-popup-label]' );
	const form = document.querySelector( '[data-bolt-edit-form]' );
	const textEditor = document.querySelector( '[data-bolt-edit-text-editor]' );
	const input = document.querySelector( '[data-bolt-edit-input]' );
	const richtextEditor = document.querySelector( '[data-bolt-edit-richtext-editor]' );
	const richtextInput = document.querySelector( '[data-bolt-edit-richtext-input]' );
	const tools = Array.from( document.querySelectorAll( '[data-bolt-edit-command]' ) );
	const linkRow = document.querySelector( '[data-bolt-edit-link-row]' );
	const linkInput = document.querySelector( '[data-bolt-edit-link-input]' );
	const linkApply = document.querySelector( '[data-bolt-edit-link-apply]' );
	const buttonEditor = document.querySelector( '[data-bolt-edit-button-editor]' );
	const buttonText = document.querySelector( '[data-bolt-edit-button-text]' );
	const buttonUrl = document.querySelector( '[data-bolt-edit-button-url]' );
	const imageEditor = document.querySelector( '[data-bolt-edit-image-editor]' );
	const imagePreview = document.querySelector( '[data-bolt-edit-image-preview]' );
	const replaceImage = document.querySelector( '[data-bolt-edit-replace-image]' );
	const error = document.querySelector( '[data-bolt-edit-error]' );
	const cancel = document.querySelector( '[data-bolt-edit-cancel]' );
	const save = document.querySelector( '[data-bolt-edit-save]' );

	if (

		! window.boltEditEditor ||
		! toolbar || ! toggle || ! highlight || ! popup || ! form ||
		! textEditor || ! richtextEditor || ! richtextInput || ! linkRow || ! buttonEditor || ! imageEditor
	) {
		return;
	}

	const modeButtons = Array.from( popup.querySelectorAll( '[data-bolt-edit-mode]' ) );
	const commentEditor = popup.querySelector( '[data-bolt-edit-comment-editor]' );
	const commentInput = popup.querySelector( '[data-bolt-edit-comment-input]' );
	const managedEditor = popup.querySelector( '[data-bolt-edit-managed-editor]' );
	const popupManagedLink = popup.querySelector( '[data-bolt-edit-popup-managed-link]' );
	const popupManagedText = popup.querySelector( '[data-bolt-edit-popup-managed-text]' );
	const popupNoAccess = popup.querySelector( '[data-bolt-edit-popup-noaccess]' );
	// An image needs the media library, which needs the room of the big popup.
	const isImageRegion = ( region ) => Boolean( region && region.querySelector( 'img' ) && ( region.innerText || '' ).replace( /\s+/g, '' ).length < 3 );
	const commentDetail = popup.querySelector( '[data-bolt-edit-comment-detail]' );
	let popupMode = 'edit';
	let commentTarget = null;
	let popupAnchor = null;
	let comments = [];
	const markerLayer = document.createElement( 'div' );
	markerLayer.className = 'bolt-edit-ui bolt-edit-markers';
		document.body.append( markerLayer );

	const editors = {
		text: textEditor,
		richtext: richtextEditor,
		button: buttonEditor,
		image: imageEditor,
	};

	let active = false;
	let selectedField = null;
	let highlightedTarget = null;
	let statusTimer = null;
	let pendingAttachmentId = 0;
	let lastRange = null;
	let linkRange = null;
	const labels = {
		openPencil: 'Edit',
		closePencil: 'Done',
		saved: 'Saved',
		error: 'Something went wrong. Please try again.',
		chooseImage: 'Choose image',
		useImage: 'Use image',
		postComment: 'Send',
		commentSaved: 'Comment posted',
		writeComment: 'Write a comment first.',
		saveChanges: 'Save',
		section: 'Page section',
		locationMissing: 'The original element has changed. This comment is still saved with its page context.',
		selectText: 'Select the text you want to turn into a link first.',
		editContent: 'Edit content',
		closeEditor: 'Close',
		save: 'Save',
		editManaged: 'Edit this content',
		loadingEditor: 'Loading editor...',
		updating: 'Updating...',
		discardChanges: 'You have unsaved changes. Close without saving?',
		openAdmin: 'Open in dashboard',
		...( window.boltEditEditor.labels || {} ),
	};

	// Enter creates paragraphs, not divs, in the rich text editor.
	document.execCommand( 'defaultParagraphSeparator', false, 'p' );

	toolbar.hidden = false;

	const fieldType = ( field ) => ( editors[ field.dataset.boltEditType ] ? field.dataset.boltEditType : 'text' );

	// A button's editable text sits in its own span when the theme adds an icon.
	const buttonLabel = ( field ) => field.querySelector( '.bolt-edit-button__text' ) || field;

	/**
	 * Add a scheme to bare links, e.g. "example.com" or "hello@example.com".
	 */
	const normalizeUrl = ( value ) => {
		const url = value.trim();

		// Keep full URLs, mailto/tel/sms links, and relative links. "example.com:8080" is not a scheme.
		if ( ! url || /^([a-z][a-z0-9+.-]*:\/\/|(mailto|tel|sms):|\/|#|\?)/i.test( url ) ) {
			return url;
		}

		if ( /^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test( url ) ) {
			return `mailto:${ url }`;
		}

		return `https://${ url }`;
	};

	const showSaved = ( message = labels.saved ) => {
		window.clearTimeout( statusTimer );
		status.textContent = message;
		status.hidden = false;
		statusTimer = window.setTimeout( () => {
			status.hidden = true;
		}, 1600 );
	};

	const showError = ( message ) => {
		error.textContent = message || labels.error;
		error.hidden = false;
	};

	const setHighlight = ( target ) => {
		if ( ! active || ! target ) {
			highlight.hidden = true;
			return;
		}

		highlightedTarget = target;
		const rect = target.getBoundingClientRect();

		highlight.style.left = `${ Math.max( 0, rect.left - 5 ) }px`;
		highlight.style.top = `${ Math.max( 0, rect.top - 5 ) }px`;
		highlight.style.width = `${ rect.width + 10 }px`;
		highlight.style.height = `${ rect.height + 10 }px`;
		// Purple means you can type here. Gray means the content is edited in a popup or can only be commented on.
		highlight.classList.toggle( 'bolt-edit-highlight--indirect', ! target.hasAttribute( 'data-bolt-edit-field' ) );
		highlight.hidden = false;
	};

	const positionPopup = ( field ) => {
		const rect = field.getBoundingClientRect();
		const gap = 12;
		const edge = 16;
		const popupRect = popup.getBoundingClientRect();
		// Keep the popup beside the clicked point, including within large containers.
		const anchorX = popupAnchor ? rect.left + rect.width * popupAnchor.x : rect.left;
		const anchorY = popupAnchor ? rect.top + rect.height * popupAnchor.y : rect.bottom;
		const left = Math.min(
			Math.max( anchorX, edge ),
			Math.max( edge, window.innerWidth - popupRect.width - edge )
		);
		let top = anchorY + gap;

		if ( top + popupRect.height > window.innerHeight - edge ) {
			top = Math.max( edge, ( popupAnchor ? anchorY : rect.top ) - popupRect.height - gap );
		}

		popup.style.right = 'auto';
		popup.style.bottom = 'auto';
		popup.style.left = `${ left }px`;
		popup.style.top = `${ top }px`;
		const finalRect = popup.getBoundingClientRect();
		if ( finalRect.bottom > window.innerHeight - edge ) {
			popup.style.top = `${ Math.max( edge, window.innerHeight - finalRect.height - edge ) }px`;
		}
	};

	const resizeInput = () => {
		const minimumHeight = 42;
		const maximumHeight = 220;

		input.style.height = 'auto';
		input.style.height = `${ Math.min( Math.max( input.scrollHeight, minimumHeight ), maximumHeight ) }px`;
		input.style.overflowY = input.scrollHeight > maximumHeight ? 'auto' : 'hidden';

		if ( selectedField && ! popup.hidden ) {
			positionPopup( selectedField );
		}
	};

	/*
	 * Rich text helpers.
	 */

	const selectRange = ( range ) => {
		const selection = window.getSelection();

		selection.removeAllRanges();
		selection.addRange( range );
	};

	const getEditorRange = () => {
		const selection = window.getSelection();

		if ( ! selection.rangeCount ) {
			return null;
		}

		const range = selection.getRangeAt( 0 );

		return richtextInput.contains( range.commonAncestorContainer ) ? range : null;
	};

	const getLinkAt = ( range ) => {
		if ( ! range ) {
			return null;
		}

		const node = range.startContainer;
		const element = Node.ELEMENT_NODE === node.nodeType ? node : node.parentElement;
		const link = element ? element.closest( 'a' ) : null;

		return link && richtextInput.contains( link ) ? link : null;
	};

	const focusEditor = () => {
		richtextInput.focus();

		if ( lastRange ) {
			selectRange( lastRange );
		}
	};

	const updateTools = () => {
		if ( richtextEditor.hidden ) {
			return;
		}

		tools.forEach( ( tool ) => {
			const command = tool.dataset.boltEditCommand;
			const pressed = 'link' === command
				? Boolean( getLinkAt( lastRange ) )
				: document.queryCommandState( command );

			tool.setAttribute( 'aria-pressed', pressed ? 'true' : 'false' );
		} );
	};

	const hideLinkRow = () => {
		linkRow.hidden = true;
		linkInput.value = '';
		linkRange = null;
	};

	const toggleLinkRow = () => {
		if ( ! linkRow.hidden ) {
			hideLinkRow();
			focusEditor();
			return;
		}

		const range = getEditorRange() || lastRange;
		const link = getLinkAt( range );

		if ( ! range || ( range.collapsed && ! link ) ) {
			showError( labels.selectText );
			return;
		}

		error.hidden = true;

		if ( link ) {
			linkRange = document.createRange();
			linkRange.selectNodeContents( link );
		} else {
			linkRange = range.cloneRange();
		}

		linkInput.value = link ? link.getAttribute( 'href' ) || '' : '';
		linkRow.hidden = false;
		positionPopup( selectedField );
		linkInput.focus();
		linkInput.select();
	};

	const applyLink = () => {
		const url = normalizeUrl( linkInput.value );
		const range = linkRange;

		hideLinkRow();
		richtextInput.focus();

		if ( range ) {
			selectRange( range );
		}

		// An empty address removes the link.
		document.execCommand( url ? 'createLink' : 'unlink', false, url || null );
		updateTools();
	};

	const placeCaretAtEnd = ( element ) => {
		const range = document.createRange();

		range.selectNodeContents( element );
		range.collapse( false );
		selectRange( range );
	};

	const replaceTag = ( element, tagName ) => {
		const replacement = element.ownerDocument.createElement( tagName );

		replacement.append( ...element.childNodes );
		element.replaceWith( replacement );
	};

	/**
	 * Turn browser editing markup into the tags Bolt Edit stores. The server sanitizes again.
	 */
	const cleanRichtext = ( html ) => {
		// An inert document, so nothing in the markup loads or runs.
		const container = document.implementation.createHTMLDocument( '' ).createElement( 'div' );

		// Parsing the serialized HTML also moves lists that Chrome nests inside <p> out of it.
		container.innerHTML = html;
		container.querySelectorAll( 'div' ).forEach( ( element ) => replaceTag( element, 'p' ) );
		container.querySelectorAll( 'b' ).forEach( ( element ) => replaceTag( element, 'strong' ) );
		container.querySelectorAll( 'i' ).forEach( ( element ) => replaceTag( element, 'em' ) );
		container.querySelectorAll( 'p' ).forEach( ( element ) => {
			if ( ! element.textContent.trim() ) {
				element.remove();
			}
		} );

		return container.innerHTML.trim();
	};

	/*
	 * Popup.
	 */

	const closePopup = () => {
		popup.hidden = true;
		popup.classList.remove( 'bolt-edit-popup--wide', 'bolt-edit-popup--detail' );
		save.disabled = false;
		const focusTarget = selectedField;
		selectedField = null;
		commentTarget = null;
		popupAnchor = null;
		popupMode = 'edit';
		commentInput.value = '';
		if ( focusTarget && focusTarget.hasAttribute( 'data-bolt-edit-field' ) ) { focusTarget.focus( { preventScroll: true } ); }
		toggle.disabled = false;
		error.hidden = true;
		error.textContent = '';
		input.style.height = '';
		richtextInput.innerHTML = '';
		pendingAttachmentId = 0;
		lastRange = null;
		hideLinkRow();
	};

	const openPopup = ( field ) => {
		const type = fieldType( field );

		selectedField = field;
		popupLabel.textContent = targetLabel( field );
		modeButtons.forEach( button => { button.hidden = button.dataset.boltEditMode === 'edit' && ! field.matches( '[data-bolt-edit-field], [data-bolt-edit-managed]' ); } );
		commentInput.value = '';
		if ( ! commentTarget ) { commentTarget = anchorTarget( field ); }
		popupMode = 'edit';
		managedEditor.hidden = true;
		commentEditor.hidden = true;
		commentDetail.hidden = true;
		save.hidden = false;
		save.textContent = labels.saveChanges;
		modeButtons.forEach( button => button.setAttribute( 'aria-selected', String( button.dataset.boltEditMode === 'edit' ) ) );
		Object.keys( editors ).forEach( ( key ) => {
			editors[ key ].hidden = key !== type;
		} );
		popup.classList.toggle( 'bolt-edit-popup--wide', 'richtext' === type );
		hideLinkRow();
		error.hidden = true;
		error.textContent = '';
		popup.hidden = false;
		toggle.disabled = true;
		setHighlight( field );
		if ( ! field.hasAttribute( 'data-bolt-edit-field' ) ) {
			setPopupMode( field.hasAttribute( 'data-bolt-edit-managed' ) ? 'edit' : 'comment' );
			return;
		}

		if ( 'image' === type ) {
			pendingAttachmentId = Number.parseInt( field.dataset.boltEditAttachmentId, 10 ) || 0;
			imagePreview.src = field.currentSrc || field.src;
			imagePreview.alt = field.alt || '';
			positionPopup( field );
			window.requestAnimationFrame( () => replaceImage.focus() );
			return;
		}

		if ( 'richtext' === type ) {
			lastRange = null;
			richtextInput.innerHTML = field.innerHTML;
			positionPopup( field );
			window.requestAnimationFrame( () => {
				richtextInput.focus();
				placeCaretAtEnd( richtextInput );
				updateTools();
			} );
			return;
		}

		if ( 'button' === type ) {
			const href = field.getAttribute( 'href' ) || '';

			buttonText.value = buttonLabel( field ).textContent.trim();
			buttonText.maxLength = Number.parseInt( field.dataset.boltEditMaxLength, 10 ) || 40;
			buttonUrl.value = '#' === href ? '' : href;
			positionPopup( field );
			window.requestAnimationFrame( () => {
				buttonText.focus();
				buttonText.select();
			} );
			return;
		}

		input.value = field.textContent.trim();
		input.maxLength = Number.parseInt( field.dataset.boltEditMaxLength, 10 ) || 160;
		resizeInput();
		window.requestAnimationFrame( () => {
			input.focus();
			input.select();
		} );
	};

	const setActive = ( nextActive ) => {
		active = nextActive;
		document.documentElement.classList.toggle( 'bolt-edit-selection-active', active );
		toggle.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		( toggle.querySelector( '[data-bolt-edit-toggle-label]' ) || toggle ).textContent = active ? labels.closePencil : labels.openPencil;

		fields.forEach( ( field ) => {
			field.tabIndex = active ? 0 : -1;
		} );

		renderMarkers();
		if ( ! active ) {
			closePopup();
			highlight.hidden = true;
			status.hidden = true;
		}
	};

	const getSubmittedValue = ( type ) => {
		switch ( type ) {
			case 'image':
				return pendingAttachmentId;

			case 'richtext':
				return cleanRichtext( richtextInput.innerHTML );

			case 'button':
				return {
					text: buttonText.value,
					url: normalizeUrl( buttonUrl.value ),
				};

			default:
				return input.value;
		}
	};

	const updateField = ( field, data ) => {
		switch ( data.type ) {
			case 'image':
				if ( ! data.image ) {
					return;
				}

				field.dataset.boltEditAttachmentId = String( data.image.id );
				field.classList.remove( 'bolt-edit-image-placeholder' );
				field.src = data.image.url;
				field.alt = data.image.alt;
				field.width = data.image.width;
				field.height = data.image.height;

				if ( data.image.srcset ) {
					field.srcset = data.image.srcset;
				} else {
					field.removeAttribute( 'srcset' );
				}

				if ( data.image.sizes ) {
					field.sizes = data.image.sizes;
				} else {
					field.removeAttribute( 'sizes' );
				}
				return;

			case 'richtext':
				// Sanitized by the server before it is returned.
				field.innerHTML = data.value;
				return;

			case 'button':
				buttonLabel( field ).textContent = data.value.text;
				field.setAttribute( 'href', data.value.url );
				return;

			default:
				field.textContent = data.value;
		}
	};

	const saveField = async () => {
		if ( popupMode === 'comment' ) {
			await saveComment();
			return;
		}
		if ( popupMode !== 'edit' || ! selectedField?.hasAttribute( 'data-bolt-edit-field' ) || save.disabled ) {
			return;
		}

		const savedFieldId = selectedField.dataset.boltEditField;

		save.disabled = true;
		cancel.disabled = true;
		modeButtons.forEach( button => { button.disabled = true; } );
		error.hidden = true;

		try {
			const response = await window.fetch(
				`${ window.boltEditEditor.restUrl }${ encodeURIComponent( savedFieldId ) }`,
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': window.boltEditEditor.nonce,
					},
					body: JSON.stringify( {
						value: getSubmittedValue( fieldType( selectedField ) ),
						page_id: Number.parseInt( window.boltEditEditor.pageId, 10 ) || 0,
					} ),
				}
			);
			let data = null;

			try {
				data = await response.json();
			} catch ( parseError ) {
				// A PHP error or proxy page instead of JSON.
				data = null;
			}

			if ( ! response.ok || ! data ) {
				throw new Error( data && data.message ? data.message : labels.error );
			}

			fields
				.filter( ( field ) => field.dataset.boltEditField === savedFieldId )
				.forEach( ( field ) => updateField( field, data ) );

			const savedField = selectedField;
			closePopup();
			setHighlight( savedField );
			showSaved();
		} catch ( saveError ) {
			showError( saveError.message );
		} finally {
			save.disabled = false;
			cancel.disabled = false;
			modeButtons.forEach( button => { button.disabled = false; } );
		}
	};

	const clientEditUrl = ( value ) => {
		if ( ! value ) { return ''; }
		try {
			const url = new URL( value, window.location.href );
			if ( url.origin !== window.location.origin || ! [ 'http:', 'https:' ].includes( url.protocol ) ) { return ''; }
			if ( window.boltEditEditor.clientMode ) { url.searchParams.set( 'bolt_edit_return', window.location.href.split( '#' )[ 0 ] ); }
			return url.href;
		} catch ( e ) { return ''; }
	};

	/*
	 * Provider editors in a popup.
	 *
	 * The edit screen opens in a frame on top of the page. After a save, only the
	 * managed regions that belong to it are swapped. If that fails, the page reloads
	 * and returns to the same scroll position.
	 */

	const popupEditing = Boolean( window.boltEditEditor.popupEditing );
	const restoreKey = 'bolt-edit-restore';
	let editModal = null;
	let modalTrigger = null;
	let modalSaved = false;
	let modalDirty = false;
	let modalSaving = false;

	// What the clicked region shows, so the popup can find the matching field in the edit screen by itself.
	const regionHint = ( region ) => {
		const hint = { text: '', image: '' };
		if ( ! region ) { return hint; }
		hint.text = ( region.innerText || region.textContent || '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 200 );
		const img = region.querySelector( 'img' );
		if ( img ) {
			try { hint.image = decodeURIComponent( new URL( img.currentSrc || img.src, window.location.href ).pathname.split( '/' ).pop() ); } catch ( e ) { /* No image hint. */ }
		}
		return hint;
	};

	const frameUrl = ( value, field, hint ) => {
		try {
			const url = new URL( value, window.location.href );
			if ( url.origin !== window.location.origin || ! url.pathname.includes( '/wp-admin/' ) ) { return ''; }
			url.searchParams.delete( 'bolt_edit_return' );
			url.searchParams.set( 'bolt_edit_modal', '1' );
			if ( field ) { url.searchParams.set( 'bolt_edit_field', field ); }
			if ( hint && hint.text ) { url.searchParams.set( 'bolt_edit_hint', hint.text ); }
			if ( hint && hint.image ) { url.searchParams.set( 'bolt_edit_img', hint.image ); }
			return url.href;
		} catch ( e ) { return ''; }
	};

	const reloadKeepingPosition = () => {
		try {
			window.sessionStorage.setItem( restoreKey, JSON.stringify( { url: window.location.href, y: window.scrollY, active } ) );
		} catch ( e ) { /* A plain reload still works. */ }
		window.location.reload();
	};

	const refreshAfterEdit = async () => {
		window.clearTimeout( statusTimer );
		status.textContent = labels.updating;
		status.hidden = false;

		// Themes that build or reveal content with their own scripts (sliders, masonry, scroll animations)
		// do not start those scripts inside a swapped region. A reload is the safe default, and a theme can
		// opt in to swapping with the bolt_edit_region_swap filter.
		if ( ! window.boltEditEditor.regionSwap ) {
			reloadKeepingPosition();
			return;
		}

		try {
			// A changing query string also skips page caches.
			const url = new URL( window.location.href );
			url.searchParams.set( 'bolt-edit-refresh', String( Date.now() ) );
			const response = await window.fetch( url.href, { credentials: 'same-origin', cache: 'no-store' } );
			if ( ! response.ok ) { throw new Error( 'Request failed' ); }
			const fresh = Array.from( new DOMParser().parseFromString( await response.text(), 'text/html' ).querySelectorAll( '[data-bolt-edit-managed]' ) );
			const current = Array.from( document.querySelectorAll( '[data-bolt-edit-managed]' ) );
			// Only swap when the new page has the same regions in the same order.
			if ( fresh.length !== current.length || current.some( ( element, index ) => element.dataset.boltEditManagedEditUrl !== fresh[ index ].dataset.boltEditManagedEditUrl ) ) {
				throw new Error( 'Regions changed' );
			}
			// The popup can lead to other posts, so every changed region is swapped.
			current.forEach( ( element, index ) => {
				if ( element.outerHTML === fresh[ index ].outerHTML ) { return; }
				const replacement = document.importNode( fresh[ index ], true );
				element.replaceWith( replacement );
				// Lets a theme start up sliders or animations inside the new markup again.
				replacement.dispatchEvent( new CustomEvent( 'bolt-edit:region-updated', { bubbles: true, detail: { region: replacement } } ) );
			} );
			renderMarkers();
			showSaved();
		} catch ( refreshError ) {
			reloadKeepingPosition();
		}
	};
	const closeModal = () => {
		if ( ! editModal || editModal.root.hidden ) { return; }
		if ( modalDirty && ! window.confirm( labels.discardChanges ) ) { return; }

		const saved = modalSaved;
		const trigger = modalTrigger;

		editModal.root.hidden = true;
		editModal.frame.src = 'about:blank';
		document.documentElement.classList.remove( 'bolt-edit-modal-open' );
		modalSaved = false;
		modalDirty = false;
		modalTrigger = null;

		if ( saved ) {
			refreshAfterEdit();
		} else if ( trigger && document.contains( trigger ) ) {
			trigger.focus( { preventScroll: true } );
		}
	};

	// Save shows only while the frame has unsaved changes.
	const updateModalActions = () => {
		if ( editModal ) { editModal.save.hidden = ! modalDirty; }
	};
	const buildModal = () => {
		const root = document.createElement( 'div' );
		root.className = 'bolt-edit-ui bolt-edit-modal';
		root.hidden = true;
		root.innerHTML = '<div class="bolt-edit-modal__backdrop"></div><div class="bolt-edit-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bolt-edit-modal-title"><div class="bolt-edit-modal__header"><div class="bolt-edit-modal__title" id="bolt-edit-modal-title" role="heading" aria-level="2"></div><div class="bolt-edit-modal__actions"><a class="bolt-edit-modal__admin" hidden></a><button class="bolt-edit-modal__save" type="button" hidden></button><button class="bolt-edit-modal__close" type="button"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></button></div></div><div class="bolt-edit-modal__body"><p class="bolt-edit-modal__loading" role="status"></p><iframe class="bolt-edit-modal__frame"></iframe></div></div>';
		document.body.append( root );

		const modal = {
			root,
			title: root.querySelector( '.bolt-edit-modal__title' ),
			close: root.querySelector( '.bolt-edit-modal__close' ),
			admin: root.querySelector( '.bolt-edit-modal__admin' ),
			save: root.querySelector( '.bolt-edit-modal__save' ),
			loading: root.querySelector( '.bolt-edit-modal__loading' ),
			frame: root.querySelector( '.bolt-edit-modal__frame' ),
		};

		modal.close.addEventListener( 'click', closeModal );
		modal.save.addEventListener( 'click', () => {
			modal.frame.contentWindow.postMessage( { source: 'bolt-edit', type: 'save' }, window.location.origin );
		} );
		root.querySelector( '.bolt-edit-modal__backdrop' ).addEventListener( 'click', closeModal );
		modal.frame.addEventListener( 'load', () => {
			// WordPress answers a screen the visitor may not open with its plain error page. Say so in our own words.
			try {
				const frameBody = modal.frame.contentDocument && modal.frame.contentDocument.body;
				if ( frameBody && 'error-page' === frameBody.id && popupNoAccess ) {
					window.clearTimeout( modalLoadTimer );
					modal.frame.hidden = true;
					modal.save.hidden = true;
					modal.loading.textContent = popupNoAccess.textContent.trim();
					modal.loading.hidden = false;
					return;
				}
			} catch ( e ) { /* A frame we cannot read is treated as a normal editor. */ }
			modalDirty = false;
			updateModalActions();
			if ( ! modalSaving ) { modal.loading.hidden = true; return; }
			window.setTimeout( () => {
				if ( ! modalSaving ) { return; }
				modalSaving = false;
				modal.loading.hidden = true;
			}, 800 );
		} );

		editModal = modal;
		return modal;
	};

	// If the popup never reports in, fall back to opening the editor like a normal link.
	let modalLoadTimer = 0;
	let modalContextSeen = false;

	const openEditModal = ( href, region, trigger, customTitle, field ) => {
		const url = frameUrl( href, field, field ? null : regionHint( region ) );
		if ( ! url ) { return false; }

		const modal = editModal || buildModal();
		const title = customTitle || labels.editContent;

		closePopup();
		highlight.hidden = true;
		modalTrigger = trigger;
		// The full post in the dashboard is always one click away.
		modal.admin.textContent = labels.openAdmin;
		modal.admin.href = clientEditUrl( href ) || href;
		modal.admin.hidden = false;
		modalSaved = false;
		modalDirty = false;
		modal.title.textContent = title;
		modal.frame.title = title;
		modal.close.setAttribute( 'aria-label', labels.closeEditor );
		modal.close.title = labels.closeEditor;
		modal.save.textContent = labels.save;
		modal.save.hidden = true;
		modal.loading.textContent = labels.loadingEditor;
		modal.loading.hidden = false;
		modalContextSeen = false;
		window.clearTimeout( modalLoadTimer );
		modalLoadTimer = window.setTimeout( () => {
			if ( modalContextSeen || ! editModal || editModal.root.hidden ) { return; }
			closeModal();
			window.location.href = href;
		}, 15000 );
		modal.frame.hidden = false;
		modal.frame.src = url;
		modal.root.hidden = false;
		document.documentElement.classList.add( 'bolt-edit-modal-open' );
		modal.close.focus();
		return true;
	};

	// Client buttons: remember this page, so the admin can lead back to it.
	const dashboardLink = document.querySelector( '#wp-admin-bar-bolt-edit-switch > a' );
	if ( dashboardLink ) {
		dashboardLink.addEventListener( 'click', () => {
			try {
				window.sessionStorage.setItem( 'bolt-edit-client-return', window.location.href.split( '#' )[ 0 ] );
			} catch ( e ) { /* The dashboard still opens without it. */ }
		} );
	}

	// The frame reports saves and unsaved changes with postMessage.
	window.addEventListener( 'message', ( event ) => {
		const data = event.data;

		if ( ! editModal || editModal.root.hidden || event.origin !== window.location.origin || event.source !== editModal.frame.contentWindow || ! data || 'bolt-edit' !== data.source ) { return; }

		if ( 'saved' === data.type ) {
			modalSaved = true;
			modalDirty = false;
			modalSaving = false;
			// Done: close and show the result on the page.
			closeModal();
			return;
		} else if ( 'saving' === data.type ) {
			modalSaving = true;
			editModal.loading.textContent = labels.updating;
			editModal.loading.hidden = false;
		} else if ( 'invalid' === data.type ) {
			modalSaving = false;
			editModal.loading.hidden = true;
		} else if ( 'dirty' === data.type ) {
			modalDirty = Boolean( data.value );
		} else if ( 'context' === data.type ) {
			modalContextSeen = true;
			window.clearTimeout( modalLoadTimer );
		}

		updateModalActions();
	} );
	const selectableTarget = ( element ) => {
		if ( ! ( element instanceof Element ) || element.closest( '.bolt-edit-ui, #wpadminbar, .media-modal, .media-modal-backdrop, script, style, iframe' ) ) { return null; }
		return element.closest( '[data-bolt-edit-field]' ) || element.closest( '[data-bolt-edit-managed]' ) || element;
	};

	const targetLabel = ( element ) => {
		if ( ! element ) { return labels.section; }
		return element.dataset.boltEditLabel || element.dataset.boltEditManagedLabel || element.getAttribute( 'aria-label' ) || ( element.matches( 'section, article, main, header, footer, div' ) ? ( element.querySelector( 'h1,h2,h3' )?.textContent.trim().slice( 0, 80 ) || labels.section ) : element.textContent.trim().slice( 0, 80 ) || element.tagName.toLowerCase() );
	};

	const selectorFor = ( element ) => {
		const parts = [];
		let node = element;
		while ( node && node !== document.body ) {
			if ( node.id && document.querySelectorAll( `#${ CSS.escape( node.id ) }` ).length === 1 ) { parts.unshift( `#${ CSS.escape( node.id ) }` ); break; }
			const tag = node.tagName.toLowerCase();
			const classes = Array.from( node.classList ).filter( value => ! /^(bolt-edit-|is-|has-|active|hover)/.test( value ) ).slice( 0, 3 );
			const siblings = node.parentElement ? Array.from( node.parentElement.children ).filter( child => child.tagName === node.tagName ) : [];
			parts.unshift( tag + classes.map( value => `.${ CSS.escape( value ) }` ).join( '' ) + ( siblings.length > 1 ? `:nth-of-type(${ siblings.indexOf( node ) + 1 })` : '' ) );
			node = node.parentElement;
		}
		return ( parts.length ? parts.join( ' > ' ) : 'body' ).slice( 0, 2000 );
	};

	const anchorTarget = ( element, x, y ) => {
		const rect = element.getBoundingClientRect();
		return { selector: selectorFor( element ), field: element.dataset.boltEditField || '', id: element.id || '', tag: element.tagName.toLowerCase(), label: targetLabel( element ), text: element.matches( 'section, article, main, header, footer, div, body' ) ? '' : element.textContent.trim().slice( 0, 240 ), x: typeof x === 'number' && rect.width ? Math.max( 0, Math.min( 1, ( x - rect.left ) / rect.width ) ) : 0.5, y: typeof y === 'number' && rect.height ? Math.max( 0, Math.min( 1, ( y - rect.top ) / rect.height ) ) : 0.5 };
	};

	const resolveTarget = ( target ) => {
		if ( target.field ) { const field = fields.find( element => element.dataset.boltEditField === target.field ); if ( field ) { return field; } }
		if ( target.id ) { const element = document.getElementById( target.id ); if ( element && ! element.closest( '.bolt-edit-ui' ) ) { return element; } }
		try {
			const element = target.selector ? document.querySelector( target.selector ) : null;
			if ( element && ! element.closest( '.bolt-edit-ui' ) && ( ! target.text || element.textContent.trim().slice( 0, 240 ) === target.text ) ) { return element; }
		} catch ( e ) { /* A stale selector remains available in the inbox. */ }
		if ( target.text && /^[a-z][a-z0-9-]*$/.test( target.tag ) ) {
			const matches = Array.from( document.querySelectorAll( target.tag ) ).filter( element => ! element.closest( '.bolt-edit-ui' ) && element.textContent.trim().slice( 0, 240 ) === target.text );
			if ( matches.length === 1 ) { return matches[ 0 ]; }
		}
		return null;
	};

	const setPopupMode = ( mode ) => {
		popupMode = mode;
		popup.classList.remove( 'bolt-edit-popup--detail' );
		error.hidden = true;
		modeButtons.forEach( button => button.setAttribute( 'aria-selected', String( button.dataset.boltEditMode === mode ) ) );
		Object.values( editors ).forEach( editor => { editor.hidden = true; } );
		commentEditor.hidden = mode !== 'comment';
		managedEditor.hidden = true;
		commentDetail.hidden = true;
		save.hidden = false;
		save.textContent = mode === 'comment' ? labels.postComment : labels.saveChanges;
		popup.classList.toggle( 'bolt-edit-popup--wide', mode === 'edit' && selectedField?.dataset.boltEditType === 'richtext' );
		if ( mode === 'comment' ) { commentInput.focus(); }
		else if ( selectedField?.hasAttribute( 'data-bolt-edit-field' ) ) { const type = fieldType( selectedField ); editors[ type ].hidden = false;
			if ( type === 'richtext' ) { focusEditor(); } else if ( type === 'image' ) { replaceImage.focus(); } else if ( type === 'button' ) { buttonText.focus(); } else { input.focus(); } }
		else {
			managedEditor.hidden = false;
			const editUrl = selectedField?.dataset.boltEditManagedEditUrl || '';
			const url = clientEditUrl( editUrl );
			popupManagedLink.hidden = ! url;
			if ( popupNoAccess ) { popupNoAccess.hidden = Boolean( url ); }
			popupManagedLink.href = url || '#';
			popupManagedLink.dataset.boltEditOpen = 'modal';
			if ( popupManagedText ) { popupManagedText.textContent = labels.editManaged; }
			save.hidden = true;
			if ( popupEditing && editUrl && isImageRegion( selectedField ) ) {
				const region = selectedField;
				if ( openEditModal( editUrl, region, region, '', region.dataset.boltEditManagedField || '' ) ) { return; }
			}
		}
		if ( selectedField ) { positionPopup( selectedField ); }
	};
	modeButtons.forEach( button => button.addEventListener( 'click', () => setPopupMode( button.dataset.boltEditMode ) ) );

	const commentRequest = async ( url, options = {} ) => {
		const response = await window.fetch( url, { credentials: 'same-origin', cache: 'no-store', ...options, headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.boltEditEditor.nonce, ...( options.headers || {} ) } } );
		let data;
		try { data = await response.json(); } catch ( e ) { throw new Error( labels.error ); }
		if ( ! response.ok ) { throw new Error( data.message || labels.error ); }
		return data;
	};

	const saveComment = async () => {
		if ( save.disabled || ! commentTarget ) { return; }
		const text = commentInput.value.trim();
		if ( ! text ) { showError( labels.writeComment ); commentInput.focus(); return; }
		save.disabled = true; cancel.disabled = true;
		modeButtons.forEach( button => { button.disabled = true; } );
		try {
			const comment = await commentRequest( window.boltEditEditor.commentsUrl, { method: 'POST', body: JSON.stringify( { page_url: window.location.href, page_id: Number( window.boltEditEditor.pageId ) || 0, page_title: document.title.slice( 0, 191 ), target: commentTarget, comment: text } ) } );
			comments.push( comment ); closePopup(); renderMarkers(); showSaved( labels.commentSaved );
		} catch ( e ) { showError( e.message ); }
		finally { save.disabled = false; cancel.disabled = false; modeButtons.forEach( button => { button.disabled = false; } ); }
	};

	const commentEditForm = popup.querySelector( '[data-bolt-edit-comment-edit-form]' );
	const commentEditInput = popup.querySelector( '[data-bolt-edit-comment-edit-input]' );
	const commentEditButton = popup.querySelector( '[data-bolt-edit-comment-edit]' );
	const commentEditSave = popup.querySelector( '[data-bolt-edit-comment-edit-save]' );
	const commentDeleteButton = popup.querySelector( '[data-bolt-edit-comment-delete]' );
	let deleteTimer = 0;
	let openedComment = null;

	const commentDate = ( value ) => {
		const date = new Date( String( value ).replace( ' ', 'T' ) + 'Z' );
		return isNaN( date.getTime() ) ? null : date;
	};

	// The comment as it is read: who, status and date, the text, and for your own comments an Edit link.
	const renderCommentDetail = ( comment ) => {
		openedComment = comment;
		const name = String( comment.author || '' );
		const parts = name.trim().split( /\s+/ );
		popup.querySelector( '[data-bolt-edit-comment-avatar]' ).textContent = ( ( parts[ 0 ] || '' ).charAt( 0 ) + ( parts.length > 1 ? parts[ parts.length - 1 ].charAt( 0 ) : '' ) ).toUpperCase();
		popup.querySelector( '[data-bolt-edit-comment-author]' ).textContent = name;
		const meta = popup.querySelector( '[data-bolt-edit-comment-meta]' );
		meta.textContent = '';
		const created = commentDate( comment.created_at );
		meta.append( ( comment.resolved ? ( labels.statusResolved || 'Resolved' ) : ( labels.statusOpen || 'Open' ) ) + ( created ? ' · ' + created.toLocaleDateString( undefined, { day: 'numeric', month: 'short', year: 'numeric' } ) : '' ) );
		if ( comment.edited_at ) {
			const edited = document.createElement( 'span' );
			const when = commentDate( comment.edited_at );
			edited.className = 'bolt-edit-popup__edited';
			edited.textContent = labels.editedLabel || 'edited';
			if ( when ) { edited.title = when.toLocaleString(); }
			meta.append( ' · ', edited );
		}
		const body = popup.querySelector( '[data-bolt-edit-comment-body]' );
		body.textContent = comment.comment;
		body.hidden = false;
		commentEditForm.hidden = true;
		commentEditButton.hidden = ! comment.mine;
	};

	const startCommentEdit = () => {
		if ( ! openedComment || ! openedComment.mine ) { return; }
		commentEditInput.value = openedComment.comment;
		if ( 'function' === typeof resetDeleteButton ) { resetDeleteButton(); }
		popup.querySelector( '[data-bolt-edit-comment-body]' ).hidden = true;
		commentEditButton.hidden = true;
		commentEditForm.hidden = false;
		error.hidden = true;
		commentEditInput.focus();
		if ( selectedField ) { positionPopup( selectedField ); }
	};

	const saveCommentEdit = async () => {
		if ( ! openedComment ) { return; }
		const text = commentEditInput.value.trim();
		if ( ! text ) { showError( labels.writeComment ); commentEditInput.focus(); return; }
		if ( text === openedComment.comment ) { renderCommentDetail( openedComment ); if ( selectedField ) { positionPopup( selectedField ); } return; }
		commentEditSave.disabled = true;
		try {
			const updated = await commentRequest( `${ window.boltEditEditor.commentsUrl }/${ openedComment.id }/text`, { method: 'PATCH', body: JSON.stringify( { comment: text } ) } );
			const at = comments.findIndex( item => item.id === updated.id );
			if ( at > -1 ) { comments[ at ] = updated; }
			renderCommentDetail( updated );
			showSaved( labels.commentEdited || labels.commentSaved );
			if ( selectedField ) { positionPopup( selectedField ); }
		} catch ( e ) { showError( e.message ); }
		finally { commentEditSave.disabled = false; }
	};

	commentEditButton.addEventListener( 'click', startCommentEdit );
	commentEditSave.addEventListener( 'click', saveCommentEdit );
	const resetDeleteButton = () => {
		window.clearTimeout( deleteTimer );
		commentDeleteButton.textContent = labels.deleteLabel || 'Delete';
		commentDeleteButton.dataset.sure = '';
	};

	const deleteOwnComment = async () => {
		if ( ! openedComment || ! openedComment.mine ) { return; }
		if ( ! commentDeleteButton.dataset.sure ) {
			commentDeleteButton.dataset.sure = '1';
			commentDeleteButton.textContent = labels.deleteSure || 'Click again to delete';
			deleteTimer = window.setTimeout( resetDeleteButton, 4000 );
			return;
		}
		resetDeleteButton();
		commentDeleteButton.disabled = true;
		try {
			const gone = openedComment.id;
			await commentRequest( `${ window.boltEditEditor.commentsUrl }/${ gone }/own`, { method: 'DELETE' } );
			comments = comments.filter( item => item.id !== gone );
			closePopup();
			renderMarkers();
			showSaved( labels.commentDeleted );
		} catch ( e ) { showError( e.message ); }
		finally { commentDeleteButton.disabled = false; }
	};

	commentDeleteButton.addEventListener( 'click', deleteOwnComment );

	const openComment = ( comment ) => {
		const target = resolveTarget( comment.target );
		if ( target ) { target.scrollIntoView( { block: 'center', behavior: 'instant' } ); }
		selectedField = target || document.body;
		popupAnchor = target ? comment.target : null;
		popupMode = 'detail';
		popupLabel.textContent = comment.target.label;
		Object.values( editors ).forEach( editor => { editor.hidden = true; } );
		commentEditor.hidden = true; managedEditor.hidden = true; commentDetail.hidden = false;
		renderCommentDetail( comment );
		popup.classList.add( 'bolt-edit-popup--detail' );
		modeButtons.forEach( button => { button.hidden = true; } );
		save.hidden = true; error.hidden = Boolean( target );
		error.textContent = target ? '' : labels.locationMissing;
		popup.hidden = false; toggle.disabled = true;
		if ( target ) { setHighlight( target ); }
		positionPopup( selectedField ); cancel.focus();
	};

	const renderMarkers = () => {
		markerLayer.replaceChildren();
		comments.filter( comment => ! comment.resolved ).forEach( comment => {
			const target = resolveTarget( comment.target ); if ( ! target ) { return; }
			const rect = target.getBoundingClientRect();
			if ( ! rect.width || ! rect.height ) { return; }
			const x = rect.left + rect.width * comment.target.x; const y = rect.top + rect.height * comment.target.y;
			if ( y < 0 || y > window.innerHeight || x < 0 || x > window.innerWidth ) { return; }
			const marker = document.createElement( 'button' ); marker.type = 'button'; marker.className = comment.mine ? 'bolt-edit-comment-marker' : 'bolt-edit-comment-marker bolt-edit-comment-marker--other'; marker.textContent = String( comment.id ); marker.setAttribute( 'aria-label', `${ comment.target.label }: ${ comment.comment.slice( 0, 80 ) }` ); marker.style.left = `${ x }px`; marker.style.top = `${ y }px`;
			marker.addEventListener( 'click', () => { if ( ! save.disabled ) { closePopup(); openComment( comment ); } } );markerLayer.append( marker );
		} );
	};

	const loadComments = async () => {
		if ( ! window.boltEditEditor.commentsUrl ) { return; }
		try {
			const url = new URL( window.boltEditEditor.commentsUrl ); url.searchParams.set( 'page_url', window.location.href );
			comments = await commentRequest( url.href ); renderMarkers();
			const id = Number( new URLSearchParams( window.location.search ).get( 'bolt-edit-comment' ) );
			if ( id ) {
				const comment = comments.find( item => item.id === id ) || await commentRequest( `${ window.boltEditEditor.commentsUrl }/${ id }` );
				if ( comment ) { setActive( true ); openComment( comment ); }
			}
		} catch ( e ) { showSaved( e.message ); }
	};
	loadComments();

	/*
	 * Events.
	 */

	toggle.addEventListener( 'click', () => {
		if ( toggle.disabled ) {
			return;
		}

		setActive( ! active );
	} );

	document.addEventListener( 'pointerover', ( event ) => {
		const field = selectableTarget( event.target );

		if ( active && ! selectedField && field ) {
			setHighlight( field );
		}
	} );

	document.addEventListener( 'pointerout', ( event ) => {
		const field = selectableTarget( event.target );

		if (
			active &&
			! selectedField &&
			field &&
			! field.contains( event.relatedTarget ) &&
			! highlight.contains( event.relatedTarget )
		) {
			highlight.hidden = true;
		}
	} );

	document.addEventListener(
		'click',
		( event ) => {
			if ( ! active || selectedField ) { return; }
			const field = selectableTarget( event.target );
			if ( ! field ) { return; }
			event.preventDefault();
			event.stopPropagation();
			commentTarget = anchorTarget( field, event.clientX, event.clientY );
			// Keyboard-generated clicks use the element-based positioning fallback.
			popupAnchor = event.detail > 0 ? commentTarget : null;
			openPopup( field );
		},
		true
	);

	fields.forEach( ( field ) => {
		field.addEventListener( 'focus', () => {
			if ( active && ! selectedField ) {
				setHighlight( field );
			}
		} );

		field.addEventListener( 'keydown', ( event ) => {
			if ( active && 'Enter' === event.key && ! selectedField ) {
				event.preventDefault();
				openPopup( field );
			}
		} );
	} );

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		saveField();
	} );

	cancel.addEventListener( 'click', closePopup );

	// Managed content opens in a popup. Modified clicks and other sites keep the normal link.
	[ popupManagedLink ].forEach( ( link ) => {
		link.addEventListener( 'click', ( event ) => {
			if ( ! popupEditing || event.defaultPrevented || 0 !== event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) { return; }
			const region = selectedField;
			if ( ! region || ! region.hasAttribute( 'data-bolt-edit-managed' ) || 'admin' === link.dataset.boltEditOpen ) { return; }
			if ( openEditModal( link.href, region, link, '', region.dataset.boltEditManagedField || '' ) ) { event.preventDefault(); }
		} );
	} );

	tools.forEach( ( tool ) => {
		// Keep the text selection in the editor when a tool is clicked.
		tool.addEventListener( 'mousedown', ( event ) => event.preventDefault() );

		tool.addEventListener( 'click', () => {
			const command = tool.dataset.boltEditCommand;

			if ( 'link' === command ) {
				toggleLinkRow();
				return;
			}

			focusEditor();
			document.execCommand( command, false, null );
			updateTools();
		} );
	} );

	document.addEventListener( 'selectionchange', () => {
		const range = getEditorRange();

		if ( range ) {
			lastRange = range.cloneRange();
		}

		updateTools();
	} );

	richtextInput.addEventListener( 'paste', ( event ) => {
		// Paste as plain text so formatting from Word or other sites is not carried over.
		event.preventDefault();
		document.execCommand( 'insertText', false, event.clipboardData.getData( 'text/plain' ) );
	} );

	richtextInput.addEventListener( 'input', () => {
		if ( selectedField ) {
			positionPopup( selectedField );
		}
	} );

	richtextInput.addEventListener( 'keydown', ( event ) => {
		const modifier = event.metaKey || event.ctrlKey;

		if ( modifier && 'Enter' === event.key ) {
			event.preventDefault();
			saveField();
		} else if ( modifier && 'k' === event.key.toLowerCase() ) {
			event.preventDefault();
			toggleLinkRow();
		}
	} );

	linkApply.addEventListener( 'click', applyLink );

	linkInput.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && ! event.isComposing ) {
			// Apply the link instead of submitting and saving the whole field.
			event.preventDefault();
			applyLink();
		}
	} );

	replaceImage.addEventListener( 'click', () => {
		if ( ! window.wp || ! window.wp.media ) {
			showError( labels.error );
			return;
		}

		const mediaFrame = window.wp.media( {
			title: labels.chooseImage,
			button: {
				text: labels.useImage,
			},
			library: {
				type: 'image',
			},
			multiple: false,
		} );

		mediaFrame.state( 'library' ).set( 'content', 'browse' );

		if ( pendingAttachmentId ) {
			const currentAttachment = window.wp.media.attachment( pendingAttachmentId );

			currentAttachment.fetch();
			mediaFrame.state( 'library' ).get( 'selection' ).add( currentAttachment );
		}

		mediaFrame.on( 'open', () => {
			document.documentElement.classList.add( 'bolt-edit-media-open' );
			mediaFrame.content.mode( 'browse' );
			window.requestAnimationFrame( () => {
				const modalContainer = document.querySelector( '.media-modal .media-frame' );

				if ( modalContainer && modalContainer.getBoundingClientRect().height <= 0 ) {
					console.warn( 'Bolt Edit: the media modal is collapsed, probably because theme CSS targets a WordPress UI class such as .media-frame.' );
				}
			} );
		} );

		mediaFrame.on( 'close', () => {
			document.documentElement.classList.remove( 'bolt-edit-media-open' );

			if ( selectedField ) {
				setHighlight( selectedField );
				positionPopup( selectedField );
			}
		} );

		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			const preview = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			pendingAttachmentId = Number.parseInt( attachment.id, 10 );
			imagePreview.src = preview;
			imagePreview.alt = attachment.alt || '';
			error.hidden = true;
			positionPopup( selectedField );
		} );

		mediaFrame.open();
	} );

	input.addEventListener( 'input', resizeInput );

	input.addEventListener( 'keydown', ( event ) => {
		if ( popupMode === 'edit' && 'Enter' === event.key && ! event.isComposing && 'text' === selectedField?.dataset.boltEditType ) {
			event.preventDefault();
			saveField();
		}
	} );

	// Escape closes an open popup first, wherever the focus is on the page. A second Escape then leaves edit mode.
	window.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' !== event.key || event.defaultPrevented || event.isComposing || popup.hidden || save.disabled ) { return; }
		if ( editModal && ! editModal.root.hidden ) { return; }
		if ( ! linkRow.hidden ) { return; }
		if ( document.querySelector( '.media-modal:not([style*="display: none"])' ) ) { return; }
		event.preventDefault();
		event.stopImmediatePropagation();
		closePopup();
	}, true );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && editModal && ! editModal.root.hidden ) {
			closeModal();
			return;
		}

		if ( 'Escape' !== event.key || save.disabled ) {
			return;
		}

		if ( ! linkRow.hidden ) {
			hideLinkRow();
			focusEditor();
		} else if ( selectedField ) {
			closePopup();
		} else if ( active ) {
			setActive( false );
			toggle.focus();
		}
	} );

	window.addEventListener( 'resize', () => {
		renderMarkers();
		if ( selectedField ) {
			setHighlight( selectedField );
			positionPopup( selectedField );
		}
	} );

	window.addEventListener(
		'scroll',
		( event ) => {
			renderMarkers();
			// Scrolling inside the popup's editor must not move the popup.
			if ( popup.contains( event.target ) ) {
				return;
			}

			if ( selectedField ) {
				setHighlight( selectedField );
				positionPopup( selectedField );
			} else {
				highlight.hidden = true;
			}
		},
		true
	);

	try {
		const restored = JSON.parse( window.sessionStorage.getItem( restoreKey ) || 'null' );
		window.sessionStorage.removeItem( restoreKey );
		if ( restored && restored.url === window.location.href ) {
			const scrollBack = () => window.scrollTo( 0, Number( restored.y ) || 0 );
			scrollBack();
			window.addEventListener( 'load', scrollBack, { once: true } );
			if ( restored.active ) { setActive( true ); }
		}
	} catch ( e ) { /* Nothing to restore. */ }

	const requestParams = new URLSearchParams( window.location.search );

	if ( '1' === requestParams.get( 'bolt-edit-edit' ) ) {
		setActive( true );

		const requestedFieldId = requestParams.get( 'bolt-edit-field' );
		const requestedField = requestedFieldId
			? fields.find( ( field ) => field.dataset.boltEditField === requestedFieldId )
			: null;

		if ( requestedField ) {
			window.requestAnimationFrame( () => {
				requestedField.scrollIntoView( {
					behavior: 'smooth',
					block: 'center',
				} );

				window.setTimeout( () => openPopup( requestedField ), 350 );
			} );
		}
	}
}() );
