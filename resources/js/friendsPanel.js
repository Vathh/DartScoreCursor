/** Panel znajomych w layoutcie — listy ładują się dopiero po otwarciu. */
export function registerFriendsPanel(Alpine) {
	Alpine.data('friendsPanel', (config) => ({
		open: false,
		html: '',
		loading: false,
		error: false,

		async show() {
			this.open = true;
			if (this.loading) {
				return;
			}
			this.error = false;
			this.loading = true;
			try {
				const res = await fetch(config.url, {
					headers: {
						Accept: 'text/html',
						'X-Requested-With': 'XMLHttpRequest',
					},
				});
				if (!res.ok) {
					throw new Error('friends-panel');
				}
				this.html = await res.text();
			} catch {
				this.error = true;
				this.html = '';
			} finally {
				this.loading = false;
			}
		},

		hide() {
			this.open = false;
		},
	}));
}
