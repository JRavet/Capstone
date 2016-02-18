#include <iostream>
//cURLpp
#include <curlpp/cURLpp.hpp>
#include <curlpp/Easy.hpp>
#include <curlpp/Options.hpp>
//strings and string-processing
#include <string.h>
#include <sstream>
//JSONcpp
#include <jsoncpp/json/json.h>
//mySQL Connector/c++
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include "mysql_driver.h"
//timing
#include <ctime>
#define FIRST_SRV "green"
#define SECOND_SRV "blue"
#define THIRD_SRV "red"
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
int microSec = 1000000; //used to convert from microseconds to seconds and visa versa
bool stored_matchDetails = false;
/* */
void convertNumToString(stringstream *converter, float valueToConvert, string *returnString)
{
	(*converter) << valueToConvert;
	(*returnString) += converter->str();
	converter->str("");
	converter->clear();
}
/* */
void check_guildClaim(string guildID, sql::Connection *con)
{
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement();
	try
	{
		res = stmt->executeQuery("select * from guild where guild_id=\"" + guildID +"\"");
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
		return;
	}
	if (!res->next()) //if there were no matching guild ids ...
	{
		Easy request;
		stringstream guildDetails;
		Json::Value guild_data;
		Json::Reader reader;
		/* */
		request.setOpt(cURLpp::Options::WriteStream(&guildDetails));
		request.setOpt(Url("https://api.guildwars2.com/v1/guild_details.json?guild_id=" + guildID));
		request.perform();
		/* */
		bool parsingSuccessful = reader.parse(guildDetails.str(), guild_data);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			exit(0);
		}
		/* */
		try
		{
			stmt->execute("INSERT INTO guild VALUES(\"" + guildID + "\",\"" + guild_data["guild_name"].asString() + "\",\"" + guild_data["tag"].asString() + "\");");
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;
		}
	}
	delete stmt;
	delete res;
}
/* */
void store_activityData(const Json::Value *match_data, int mapNum, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "";
	const Json::Value objectives = (*match_data)["maps"][mapNum]["objectives"];
	for (int i = 0; i < (int)objectives.size(); i++)
	{
		SQLstmt = "INSERT INTO activity_data VALUES(";
		SQLstmt += "\"" + objectives[i]["last_flipped"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["id"].asString() + "\",";
		//
		if (objectives[i]["owner"].asString() == "Red")
		{
			convertNumToString(&converter,(*match_data)["worlds"][THIRD_SRV].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Blue")
		{
			convertNumToString(&converter,(*match_data)["worlds"][SECOND_SRV].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Green")
		{
			convertNumToString(&converter,(*match_data)["worlds"][FIRST_SRV].asInt(),&SQLstmt);
		}
		else
		{
			SQLstmt += "0"; //no PPT value
		}
		check_guildClaim(objectives[i]["claimed_by"].asString(),con);
		SQLstmt += ",\"" + objectives[i]["claimed_by"].asString() + "\"";
		SQLstmt += ",15"; //tick_timer
		SQLstmt += ",\"" + objectives[i]["owner"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["claimed_at"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["id"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
		SQLstmt += ");";
		try
		{
			stmt = con->createStatement();
			stmt->execute(SQLstmt);
			delete stmt;
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;
		}
		//
	}
}
/* */
void store_matchDetails(const Json::Value *match_data, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "INSERT INTO match_details VALUES(";
	SQLstmt += "\"" + (*match_data)["id"].asString() + "\"";
	SQLstmt += ",3"; //weekNumber
	SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
	SQLstmt += ",\"" + (*match_data)["end_time"].asString() + "\",";
	convertNumToString(&converter, (*match_data)["worlds"][FIRST_SRV].asInt(),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter, (*match_data)["worlds"][SECOND_SRV].asInt(),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter, (*match_data)["worlds"][THIRD_SRV].asInt(),&SQLstmt);
	SQLstmt += ");";
	//
	try
	{
		stmt = con->createStatement();
		stmt->execute(SQLstmt);
		delete stmt;
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
}
/* */
void store_mapScores(const Json::Value *match_data, int mapNum, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	time_t t = time(NULL); //get current local time
    struct tm * UTCTime = gmtime( & t ); //convert current time to UTC
    string SQLstmt = "INSERT INTO map_scores VALUES(\"";
    convertNumToString(&converter,((*UTCTime).tm_year + 1900),&SQLstmt);
    SQLstmt += "-";
    convertNumToString(&converter,((*UTCTime).tm_mon + 1),&SQLstmt);
    SQLstmt += "-";
    convertNumToString(&converter,((*UTCTime).tm_mday),&SQLstmt);
    SQLstmt += " ";
    convertNumToString(&converter,((*UTCTime).tm_hour),&SQLstmt);
    SQLstmt += ":";
    convertNumToString(&converter,((*UTCTime).tm_min),&SQLstmt);
    SQLstmt += ":";
    convertNumToString(&converter,((*UTCTime).tm_sec),&SQLstmt);
    SQLstmt += "\",\"" + (*match_data)["id"].asString() + "\"";
    SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
    SQLstmt += ",\"" + (*match_data)["maps"][mapNum]["type"].asString() + "\",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][FIRST_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][SECOND_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][THIRD_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][FIRST_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][SECOND_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][THIRD_SRV].asInt()),&SQLstmt);
    SQLstmt += ");";
    try
	{
		stmt = con->createStatement();
		stmt->execute(SQLstmt);
		delete stmt;
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
}
/* */
void get_matchDetails(string matchID, sql::Connection *con)
{
		Easy request;
		stringstream matchDetails;
		Json::Value match_data;
		Json::Reader parser;
		/* */
		request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
		request.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches/" + matchID));
		request.perform(); //TODO: consider ?ids=all?
		/* */
		bool parsingSuccessful = parser.parse(matchDetails.str(), match_data);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< parser.getFormattedErrorMessages();
			exit(0);
		}
		/* */
		if (!stored_matchDetails)
		{
			store_matchDetails(&match_data, con);
			stored_matchDetails = true;
		}
		for (int i = 0; i < (int)match_data["maps"].size(); i++)
		{
			store_activityData(&match_data, i, con);
			store_mapScores(&match_data, i, con);
		}
}
int main (int argc, char *argv[])
{
    try 
    {
    	sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
		driver = sql::mysql::get_mysql_driver_instance();
		con = driver->connect("tcp://127.0.0.1:3306", "root", "egamirrorimeht");
		stmt = con->createStatement();
		stmt->execute("USE Gw2Analyser");
		delete stmt;
		//
		clock_t beginTime, endTime;
		double elapsed_msecs;
    	while (1)
		{
			beginTime = clock();
			get_matchDetails("1-4", con);
			endTime = clock();
			elapsed_msecs = double(endTime - beginTime) / CLOCKS_PER_SEC * microSec;
			cout << elapsed_msecs << endl;
			usleep(microSec*60 - elapsed_msecs);
		}
		delete con;
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
	return 0;
}
