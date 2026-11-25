/**
 * Episode Player: replaces native audio controls with a custom UI.
 * Works with any number of players on the page. Without JS the native controls stay.
 */
( function () {
	const format = ( t ) => {
		if ( ! isFinite( t ) ) {
			return '0:00';
		}
		t = Math.floor( t );
		const h = Math.floor( t / 3600 );
		const m = Math.floor( ( t % 3600 ) / 60 );
		const s = String( t % 60 ).padStart( 2, '0' );
		return h ? `${ h }:${ String( m ).padStart( 2, '0' ) }:${ s }` : `${ m }:${ s }`;
	};

	const setup = ( root ) => {
		const audio = root.querySelector( '.pc-player__audio' );
		const controls = root.querySelector( '.pc-player__controls' );
		if ( ! audio || ! controls ) {
			return;
		}
		const toggle = controls.querySelector( '.pc-player__toggle' );
		const seek = controls.querySelector( '.pc-player__seek' );
		const current = controls.querySelector( '.pc-player__current' );
		const total = controls.querySelector( '.pc-player__duration' );
		let seeking = false;

		audio.removeAttribute( 'controls' );
		audio.hidden = true;
		controls.hidden = false;

		const paint = () => {
			const pct = seek.max > 0 ? ( seek.value / seek.max ) * 100 : 0;
			seek.style.setProperty( '--pc-progress', pct + '%' );
		};
		const setPlaying = ( playing ) => {
			root.classList.toggle( 'is-playing', playing );
			toggle.setAttribute( 'aria-label', playing ? toggle.dataset.labelPause : toggle.dataset.labelPlay );
		};

		toggle.addEventListener( 'click', () => ( audio.paused ? audio.play() : audio.pause() ) );
		audio.addEventListener( 'play', () => {
			document.querySelectorAll( '.pc-player__audio' ).forEach( ( other ) => other !== audio && other.pause() );
			setPlaying( true );
		} );
		audio.addEventListener( 'pause', () => setPlaying( false ) );
		audio.addEventListener( 'ended', () => setPlaying( false ) );
		audio.addEventListener( 'loadedmetadata', () => {
			if ( isFinite( audio.duration ) ) {
				seek.max = Math.floor( audio.duration );
				total.textContent = format( audio.duration );
			}
		} );
		audio.addEventListener( 'timeupdate', () => {
			if ( ! seeking ) {
				seek.value = Math.floor( audio.currentTime );
				paint();
			}
			current.textContent = format( audio.currentTime );
		} );
		seek.addEventListener( 'input', () => {
			seeking = true;
			current.textContent = format( seek.value );
			paint();
		} );
		seek.addEventListener( 'change', () => {
			audio.currentTime = Number( seek.value );
			seeking = false;
		} );
	};

	const init = () => document.querySelectorAll( '.pc-player' ).forEach( setup );
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
