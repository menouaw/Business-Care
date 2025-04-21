
FROM maven:3.9-eclipse-temurin-17 AS build


WORKDIR /app


COPY pom.xml .


RUN mvn dependency:go-offline


COPY src ./src






RUN mvn package -DskipTests


RUN ls -l /app/target


FROM eclipse-temurin:17-jre-alpine


WORKDIR /app



RUN mkdir output && chown 1000:1000 output



COPY --from=build /app/target/reporting-app*.jar ./app.jar


RUN ls -l /app


RUN chmod +rx /app/app.jar





CMD java -jar /app/app.jar 
