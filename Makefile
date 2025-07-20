test:
	docker run -it --rm --name cancerstan -v "$$PWD":/usr/src/cancerstan -w /usr/src/cancerstan php:8.4-cli php ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests

run:
	docker run -it --rm --name cancerstan -v "$$PWD":/usr/src/cancerstan -w /usr/src/cancerstan php:8.4-cli php index.php --dry-run --custom-fixers=CustomFixer

setup-docker:
	docker run -it --rm --name cancerstan \
	  -v "$$PWD":/usr/src/cancerstan \
	  -w /usr/src/cancerstan \
	  php:8.4-cli \
	  bash -c "apt-get update && apt-get install -y unzip && php composer.phar install"

build:
	 php -d phar.readonly=off ./phar-composer.phar build ../cancerstan/
