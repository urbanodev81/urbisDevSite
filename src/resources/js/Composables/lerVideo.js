/*
 * Link de vídeo colado no editor do blog → { provedor, id } (bloco 3a da 246).
 * O servidor confere o id de novo (`SaneadorDeTexto::PROVEDORES`); aqui é só
 * para o editor entender o que foi colado.
 */
/** Link do YouTube ou do Vimeo → { provedor, id }, ou null. */
export function lerVideo(endereco) {
    let url;
    try {
        url = new URL(endereco.trim());
    } catch {
        return null;
    }
    const host = url.hostname.replace(/^(www\.|m\.)/, '');
    const yt = /^[A-Za-z0-9_-]{11}$/;
    if (host === 'youtu.be') {
        const id = url.pathname.slice(1).split('/')[0];
        return yt.test(id) ? { provedor: 'youtube', id } : null;
    }
    if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
        const v = url.searchParams.get('v');
        if (v && yt.test(v)) return { provedor: 'youtube', id: v };
        const m = url.pathname.match(/^\/(embed|shorts|live)\/([A-Za-z0-9_-]{11})/);
        return m ? { provedor: 'youtube', id: m[2] } : null;
    }
    if (host === 'vimeo.com' || host === 'player.vimeo.com') {
        const m = url.pathname.match(/^\/(?:video\/)?(\d{6,12})(?:\/|$)/);
        return m ? { provedor: 'vimeo', id: m[1] } : null;
    }
    return null;
}
