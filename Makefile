podman:
	podman build -t docker.io/coldfrontlabs/cinderella:latest .

podman-push:
	podman push docker.io/coldfrontlabs/cinderella:latest
