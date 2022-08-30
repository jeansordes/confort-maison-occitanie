FROM mariadb:10.9

WORKDIR /docker-entrypoint-initdb.d
RUN mkdir 1 2 3
COPY "sql/1-pre-seed/*" 1
COPY "sql/2-seed/*" 2
COPY "sql/3-post-seed/*" 3

WORKDIR 1
RUN for file in `find -name "*.sql"`; do echo $file | cut -c3- | xargs -I {} mv {} ../1-{} ; done

WORKDIR ../2
RUN for file in `find -name "*.sql"`; do echo $file | cut -c3- | xargs -I {} mv {} ../2-{} ; done

WORKDIR ../3
RUN for file in `find -name "*.sql"`; do echo $file | cut -c3- | xargs -I {} mv {} ../3-{} ; done

WORKDIR ..
RUN rm -rf 1 2 3

RUN chmod a+r *