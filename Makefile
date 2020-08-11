all: sync-local

sync-local:
	rsync -avzz --exclude="dokuwiki" --exclude="conf/" * dokuwiki/lib/plugins/authnc
