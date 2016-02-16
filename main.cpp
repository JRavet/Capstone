//VERSION 1.0.0.1
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
	(*returnString) += "," + converter->str();
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
		Json::Value root;
		Json::Reader reader;
		/* */
		request.setOpt(cURLpp::Options::WriteStream(&guildDetails));
		request.setOpt(Url("https://api.guildwars2.com/v1/guild_details.json?guild_id=" + guildID));
		request.perform();
		/* */
		bool parsingSuccessful = reader.parse(guildDetails.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			exit(0);
		}
		/* */
		try
		{
			stmt->execute("INSERT INTO guild VALUES(\"" + guildID + "\",\"" + root["guild_name"].asString() + "\",\"" + root["tag"].asString() + "\");");
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
void store_MapData(const Json::Value *root, int mapNum, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "";
	const Json::Value objectives = (*root)["maps"][mapNum]["objectives"];
	for (int i = 0; i < (int)objectives.size(); i++)
	{
		SQLstmt = "INSERT INTO activity_data VALUES(";
		SQLstmt += "\"" + objectives[i]["last_flipped"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["id"].asString() + "\"";
		//
		if (objectives[i]["owner"].asString() == "Red")
		{
			convertNumToString(&converter,(*root)["worlds"]["red"].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Blue")
		{
			convertNumToString(&converter,(*root)["worlds"]["blue"].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Green")
		{
			convertNumToString(&converter,(*root)["worlds"]["green"].asInt(),&SQLstmt);
		}
		else
		{
			SQLstmt += ",0";
		}
		check_guildClaim(objectives[i]["claimed_by"].asString(),con);
		SQLstmt += ",\"" + objectives[i]["claimed_by"].asString() + "\"";
		SQLstmt += ",15"; //tick_timer
		SQLstmt += ",\"" + objectives[i]["owner"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["time_claimed"].asString() + "\"";
		SQLstmt += ",\"" + (*root)["id"].asString() + "\"";
		SQLstmt += ",\"" + (*root)["start_time"].asString() + "\"";
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
void store_matchDetails(const Json::Value root, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "INSERT INTO match_details VALUES(";
	SQLstmt += "\"" + root["id"].asString() + "\"";
	SQLstmt += ",3"; //weekNumber
	SQLstmt += ",\"" + root["start_time"].asString() + "\"";
	SQLstmt += ",\"" + root["end_time"].asString() + "\"";
	convertNumToString(&converter, root["worlds"]["green"].asInt(),&SQLstmt);
	convertNumToString(&converter, root["worlds"]["blue"].asInt(),&SQLstmt);
	convertNumToString(&converter, root["worlds"]["red"].asInt(),&SQLstmt);
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
void get_matchDetails(string matchID, sql::Connection *con)
{
		Easy request;
		stringstream matchDetails;
		Json::Value root;
		Json::Reader reader;
		/* */
		request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
		request.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches/" + matchID));
		request.perform();
		/* */
		bool parsingSuccessful = reader.parse(matchDetails.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			exit(0);
		}
		/* */
		if (!stored_matchDetails)
		{
			store_matchDetails(root, con);
			stored_matchDetails = true;
		}
		for (int i = 0; i < (int)root["maps"].size(); i++)
		{
			store_MapData(&root, i, con);
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
    	while (1)
		{
			clock_t begin = clock();
			get_matchDetails("1-4", con);
			clock_t end = clock();
			double elapsed_msecs = double(end - begin) / CLOCKS_PER_SEC * microSec;
			//usleep(microSec*5 - elapsed_msecs);
		}
		delete con;
		/*
		Easy myRequest;
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches/1-3"));
		myRequest.perform();
		Json::Value root;
		Json::Reader reader;
		bool parsingSuccessful = reader.parse(result.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			return 1;
		}
		const Json::Value maps = root["maps"];
		storeMapData(maps[0]);

		clock_t begin = clock();

		sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
 		sql::ResultSet *res;
		driver = sql::mysql::get_mysql_driver_instance();
		con = driver->connect("tcp://127.0.0.1:3306", "root", "egamirrorimeht");

		stmt = con->createStatement();
		stmt->execute("USE Gw2Analyser");
		res = stmt->executeQuery("SELECT * from user;");
		while (res->next())
		{
			cout << res->getString(1) << endl;
			cout << res->getString(2) << endl;
		}
		delete stmt;
		delete con;
		delete res;
		clock_t end = clock();
		double elapsed_msecs = double(end - begin) / CLOCKS_PER_SEC * microSec;
		usleep(microSec*5 - elapsed_msecs);
		*/
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
