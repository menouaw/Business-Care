FROM maven:3.9-eclipse-temurin-17 AS build

WORKDIR /app

COPY pom.xml .

RUN mvn dependency:go-offline


COPY src ./src


RUN mvn package -DskipTests


FROM eclipse-temurin:17-jre-alpine


WORKDIR /app


RUN mkdir -p output && chmod 755 /app/output


COPY --from=build /app/target/reporting-app*.jar ./app.jar


RUN chmod +rx /app/app.jar


COPY --chmod=0755 ./docker-entrypoint.sh /docker-entrypoint.sh


ENTRYPOINT ["/docker-entrypoint.sh"] 
