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
//multithreading
//
#define FIRST_SRV "green"
#define SECOND_SRV "blue"
#define THIRD_SRV "red"
#define TIME_RES 60 //in seconds; standard is 60 seconds
#define MICROSEC 1000000.0 //the amount of microseconds in a second
//
#define IPADDR "tcp://127.0.0.1:3306"
#define USERNAME "root"
#define PASSWORD "egamirrorimeht"
#define DATABASE "Gw2Analyser"
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
bool stored_matchDetails = false;
bool connected = true; //TODO for the database connection
bool force_resync = false;
string previous_start_time = "";
//TODO comment functions
//TODO rename variables to be more descriptive
//TODO multithread
//TODO calc weeknum
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
	//	cout << e.what() << endl;
		return; //return from the function early
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
		try
		{
			request.perform();
			/* */
			if (reader.parse(guildDetails.str(), guild_data))
			{
				try
				{
					stmt->execute("INSERT INTO guild VALUES(\"" + guildID + "\",\"" + guild_data["guild_name"].asString() + "\",\"" + guild_data["tag"].asString() + "\");");
				}
				catch (sql::SQLException &e)
				{
				//	cout << e.what() << endl;
				}
			}
		}
		catch (exception &e)
		{
			force_resync = true;
			cout << e.what() << endl;
		}
	}
	delete stmt;
	delete res;
}
/* */
void compare_timeStamps(string time1, string time2, string *return_string, stringstream *converter)
{ //time1 > time2
	struct tm current,previous;
	strptime(time1.c_str(), "%Y-%m-%dT%H:%M:%SZ", &current); //parses a string into a 'tm' struct; using the API's format
	strptime(time2.c_str(), "%Y-%m-%d %H:%M:%S ", &previous); //parses a string into a 'tm' struct; using a SQL date-time format
	time_t t1 = mktime(&current); //converts a 'tm' struct to a time_t
	time_t t2 = mktime(&previous); //converts a 'tm' struct to a time_t
	(*return_string) = ""; //clear the return string for new input
	int totalSeconds = difftime(t1,t2); //the total time elapsed between timestamps
	int seconds=0,minutes=0,hours=0; //the 'parsed' seconds/minutes/hours
	seconds = totalSeconds % 60;
	minutes = (int) ((totalSeconds / 60) % 60);
	hours = (int) ((totalSeconds / 3600) % 60);
	convertNumToString(converter,hours,return_string); //append the hours elapsed to the return string
	(*return_string) += ":";
	convertNumToString(converter,minutes,return_string); //append the minutes elapsed to the return string
	(*return_string) += ":";
	convertNumToString(converter,seconds,return_string); //append the seconds elapsed to the return string
}
/* */
void update_activityData(const Json::Value *match_data, const Json::Value *objective, sql::Connection *con, const struct tm *UTCTime)
{
	string updateStmt = "";
	stringstream converter;
	try
	{
		sql::Statement *stmt;
		sql::ResultSet *res;
		bool updateRow = false; //initialize to false; its true if there are any updates to be made
		stmt = con->createStatement();
		string previous_data_stmt = "SELECT last_flipped, claimed_at, guild_id FROM activity_data WHERE";
		previous_data_stmt += " match_id = \"" + (*match_data)["id"].asString() + "\"";
			string start_time = (*match_data)["start_time"].asString();
			start_time[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
			start_time.erase(19,1); // ^^
			//
			string last_flipped = (*objective)["last_flipped"].asString();
			last_flipped[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
			last_flipped.erase(19,1); // ^^
		previous_data_stmt += " and start_time = \"" + start_time + "\"";
		previous_data_stmt += " and obj_id = \"" + (*objective)["id"].asString() + "\" ORDER BY timeStamp DESC LIMIT 1;";
		res = stmt->executeQuery(previous_data_stmt);
		if (res->next())
		{ //if there is a previous entry for the given objective
			string duration_claimed = "00:00:00";
			string duration_owned = "00:00:00";
			string previous_claimed_at = res->getString("claimed_at");
			string previous_last_flipped = res->getString("last_flipped");
			if (previous_claimed_at.compare("0000-00-00 00:00:00") != 0 && res->getString("guild_id").compare((*objective)["claimed_by"].asString()) != 0) //TODO calculates every time
			{ //if the previous duration claimed is NOT "0000-00-00 00:00:00" (ie, it was claimed) and there is a new claim on it
				//calculate claim duration
				string time_claimed = "";
				convertNumToString(&converter,((*UTCTime).tm_year + 1900),&time_claimed);
				time_claimed += "-";
				convertNumToString(&converter,((*UTCTime).tm_mon + 1),&time_claimed);
				time_claimed += "-";
				convertNumToString(&converter,((*UTCTime).tm_mday),&time_claimed);
				time_claimed += "T";
				convertNumToString(&converter,((*UTCTime).tm_hour),&time_claimed);
				time_claimed += ":";
				convertNumToString(&converter,((*UTCTime).tm_min),&time_claimed);
				time_claimed += ":";
				convertNumToString(&converter,((*UTCTime).tm_sec),&time_claimed);
				time_claimed += "Z";
				if ((*objective)["claimed_at"].asString().compare("") != 0)
				{ //if the objective was re-claimed, don't use the timestamp to get claim duration; use the real data
					time_claimed = (*objective)["claimed_at"].asString();
				}
				compare_timeStamps(time_claimed, previous_claimed_at, &duration_claimed, &converter);
				updateRow = true;
			}
			if (last_flipped != previous_last_flipped)
			{ //if the objective has changed ownership since last time
				//calculate owned duration
				compare_timeStamps((*objective)["last_flipped"].asString(), previous_last_flipped, &duration_owned, &converter);
				updateRow = true;
			}
			//
			if (updateRow == true)
			{
				updateStmt = "UPDATE activity_data SET duration_claimed = \"" + duration_claimed + "\", duration_owned = \"" + duration_owned + "\" WHERE match_id = \"" + (*match_data)["id"].asString() + "\" and start_time = \"" + start_time + "\" and obj_id = \"" + (*objective)["id"].asString() + "\" ORDER BY timeStamp DESC LIMIT 1;";
				stmt->execute(updateStmt);
			}
		}		
		delete stmt;
		delete res;
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
}
void store_activityData(const Json::Value *match_data, int mapNum, sql::Connection *con, double ingame_clock_time, const struct tm *UTCTime)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "";
	const Json::Value objectives = (*match_data)["maps"][mapNum]["objectives"];
	for (int i = 0; i < (int)objectives.size(); i++)
	{
		update_activityData(match_data,&objectives[i],con, UTCTime);
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
		SQLstmt += ",\"" + objectives[i]["claimed_by"].asString() + "\",";
		convertNumToString(&converter,ingame_clock_time,&SQLstmt);
		SQLstmt += ",\"" + objectives[i]["owner"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["claimed_at"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["id"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
		SQLstmt += ",NULL, NULL"; //duration_ owned/claimed; updated at a later time
		SQLstmt += ",\"";
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
		SQLstmt += "\"";
		SQLstmt += ");";
		try
		{
			stmt = con->createStatement();
			stmt->execute(SQLstmt);
		}
		catch (sql::SQLException &e)
		{
			//cout << e.what() << endl;
		}
		delete stmt;
	}
}
/* */
void get_server_populations(int grn_srv, int blu_srv, int red_srv, string *SQLstmt)
{
	Easy request;
	stringstream result;
	Json::Value server_populations;
	Json::Reader parser;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&result));
	string requestURL = "https://api.guildwars2.com/v2/worlds?ids=";
	convertNumToString(&result,grn_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,blu_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,red_srv,&requestURL);
	request.setOpt(Url(requestURL));
	try
	{
		request.perform();
		/* */
		if (parser.parse(result.str(), server_populations))
		{ //TODO not the prettiest; refine later
			if (server_populations[0]["id"].asInt() == grn_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[0]["population"].asString() + "\"";
			}
			else if (server_populations[1]["id"].asInt() == grn_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[1]["population"].asString() + "\"";
			}
			else if (server_populations[2]["id"].asInt() == grn_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[2]["population"].asString() + "\"";
			}
			if (server_populations[0]["id"].asInt() == blu_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[0]["population"].asString() + "\"";
			}
			else if (server_populations[1]["id"].asInt() == blu_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[1]["population"].asString() + "\"";
			}
			else if (server_populations[2]["id"].asInt() == blu_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[2]["population"].asString() + "\"";
			}
			if (server_populations[0]["id"].asInt() == red_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[0]["population"].asString() + "\"";
			}
			else if (server_populations[1]["id"].asInt() == red_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[1]["population"].asString() + "\"";
			}
			else if (server_populations[2]["id"].asInt() == red_srv)
			{
				(*SQLstmt) += ",\"" + server_populations[2]["population"].asString() + "\"";
			}
		}
	}
	catch (exception &e)
	{
		cout << e.what() << endl;
		force_resync = true;
	}
}
/* */
void get_weekNumber(string *weekNum, string match_time)
{
	struct tm * matchTime_tm;
	char weekNumber[3]; //2 characters + the null-character
	//
	struct tm match_start;
	strptime(match_time.c_str(), "%Y-%m-%dT%H:%M:%SZ", &match_start); //parses a string into a 'tm' struct
	time_t t = mktime(&match_start); //converts a 'tm' struct to a time_t
	matchTime_tm = localtime(&t); //converts given time to local time
	strftime (weekNumber,3,"%U",matchTime_tm); //get the weeknumber and store it in the character array
	(*weekNum) = weekNumber; //append the week number to the return-string
}
/* */
void store_matchDetails(const Json::Value *match_data, string region, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	sql::ResultSet *res;
	string SQLstmt;
	string weekNum;
	stmt = con->createStatement();
	for (int i = 0; i < (int)(*match_data).size(); i++)
	{
		if ((((*match_data)[i]["id"]).asString())[0] == region[0])
		{
			try
			{
				res = stmt->executeQuery("SELECT * FROM match_details WHERE match_id = \"" + (*match_data)[i]["id"].asString() + "\" and start_time =\"" + (*match_data)[i]["start_time"].asString() + "\";");
			}
			catch (sql::SQLException &e)
			{
				cout << e.what() << endl;
			}
			if (!res->next()) //if this set of match_details does not already exist in the DB
			{
				get_weekNumber(&weekNum, (*match_data)[i]["start_time"].asString());
				SQLstmt = "INSERT INTO match_details VALUES(";
				SQLstmt += "\"" + (*match_data)[i]["id"].asString() + "\"";
				SQLstmt += ",\"" + weekNum + "\"";
				SQLstmt += ",\"" + (*match_data)[i]["start_time"].asString() + "\"";
				SQLstmt += ",\"" + (*match_data)[i]["end_time"].asString() + "\",";
				convertNumToString(&converter, (*match_data)[i]["worlds"][FIRST_SRV].asInt(),&SQLstmt);
				SQLstmt += ",";
				convertNumToString(&converter, (*match_data)[i]["worlds"][SECOND_SRV].asInt(),&SQLstmt);
				SQLstmt += ",";
				convertNumToString(&converter, (*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt);
				get_server_populations((*match_data)[i]["worlds"][FIRST_SRV].asInt(),(*match_data)[i]["worlds"][SECOND_SRV].asInt(),(*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt);
				SQLstmt += ");";
				//
				try
				{
					stmt->execute(SQLstmt);
				}
				catch (sql::SQLException &e)
				{
					cout << e.what() << endl;
				}
			}
			try
			{
				delete res;
			}
			catch (exception &e)
			{
				cout << e.what() << endl;
			}
		}
	}
	delete stmt;
}
void get_server_ppt(const Json::Value *match_data, int mapNum, string *SQLstmt, stringstream *converter, sql::Connection *con)
{
	sql::Statement *stmt;
	sql::ResultSet *res; //TODO look at optimizing this loop; it queries the DB a lot
	stmt = con->createStatement();
	int green_ppt = 0, blue_ppt = 0, red_ppt = 0;
	for (int i = 0; i < (int)(*match_data)["maps"][mapNum]["objectives"].size(); i++)
	{
		try
		{
			res = stmt->executeQuery("SELECT ppt_value FROM objective WHERE obj_id = \"" + (*match_data)["maps"][mapNum]["objectives"][i]["id"].asString() + "\";");
			res->next();
			if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Green")
			{
				green_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Blue")
			{
				blue_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Red")
			{
				red_ppt += res->getInt("ppt_value");
			}
			delete res;
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;	
		}
	}
	(*SQLstmt) += ",";
	convertNumToString(converter,green_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,blue_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,red_ppt,SQLstmt);
	delete stmt;
}
/* */
void append_server_stats(string data, string *SQLstmt)
{
	if (data == "")
	{
		data = "0";
	}
	(*SQLstmt) += data;
}
void store_mapScores(const Json::Value *match_data, int mapNum, sql::Connection *con, const struct tm *UTCTime)
{
	stringstream converter;
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement();
	bool errorCorrected = false;
    //
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
	string start_time = (*match_data)["start_time"].asString();
	start_time[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
	start_time.erase(19,1); // ^^
	res = stmt->executeQuery("SELECT timeStamp, "FIRST_SRV"Kills, "SECOND_SRV"Kills, "THIRD_SRV"Kills, "FIRST_SRV"Deaths, "SECOND_SRV"Deaths, "THIRD_SRV"Deaths FROM map_scores WHERE match_id = \"" + (*match_data)["id"].asString() + "\" and map_id = \"" + (*match_data)["maps"][mapNum]["type"].asString() + "\" and start_time = \"" + start_time + "\" and error_corrected = 0 ORDER BY timeStamp DESC LIMIT 1;");
	int firstKills,firstDeaths,secondKills,secondDeaths,thirdKills,thirdDeaths;
	firstKills = ((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt());
	secondKills = ((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt());
	thirdKills = ((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt());
	firstDeaths = ((*match_data)["maps"][mapNum]["deaths"][FIRST_SRV].asInt());
	secondDeaths = ((*match_data)["maps"][mapNum]["deaths"][SECOND_SRV].asInt());
	thirdDeaths = ((*match_data)["maps"][mapNum]["deaths"][THIRD_SRV].asInt());
	//initialize these variables to the current data
	if (res->next())
	{ //if there was a previous data record with the specified restrictions
		if (res->getInt(FIRST_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt()) || res->getInt(SECOND_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt()) || res->getInt(THIRD_SRV"Kills") > ((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt()))
		{ //check to see if the previous data is greater than the new data. if it is...
			//API data-error detected
			//sum the previous data with the new data to get an accurate count
			firstKills += res->getInt(FIRST_SRV"Kills");
			secondKills += res->getInt(SECOND_SRV"Kills");
			thirdKills += res->getInt(THIRD_SRV"Kills");
			firstDeaths += res->getInt(FIRST_SRV"Deaths");
			secondDeaths += res->getInt(SECOND_SRV"Deaths");
			thirdDeaths += res->getInt(THIRD_SRV"Deaths");
			errorCorrected = true; //mark the new row as error corrected
		}
  	}
	convertNumToString(&converter,firstKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,secondKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,thirdKills,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,firstDeaths,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,secondDeaths,&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,thirdDeaths,&SQLstmt);
    get_server_ppt(match_data, mapNum, &SQLstmt, &converter, con);
	if (errorCorrected == true)
    {
   		SQLstmt += ",1";
    }
    else
    {
    	SQLstmt += ",0";
    }
    SQLstmt += ");";
    try
	{
		stmt->execute(SQLstmt);
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
	delete stmt;
}
/* */
void get_matchDetails(string region, sql::Connection *con, double ingame_clock_time)
{ //region: 1 = NA, 2 = EU
	Easy request;
	stringstream matchDetails;
	Json::Value all_match_data;
	Json::Reader parser;
	struct tm * UTCTime;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	request.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches?ids=all"));
	try
	{
		request.perform();
		/* */
		if (parser.parse(matchDetails.str(), all_match_data))
		{
			if (!stored_matchDetails)
			{
				store_matchDetails(&all_match_data, region, con);
				for (int k = 0; k < (int)all_match_data.size(); k++)
				{
					if ((all_match_data[k]["id"].asString())[0] == region[0]) //filter down to matches in the region's range
					{
						previous_start_time = (all_match_data[k]["start_time"].asString());
						break;
					}
				}
				stored_matchDetails = true;
			}
			//get the current time in UTC format to pass later
			time_t t = time(NULL); //get current local time
			UTCTime = gmtime( & t ); //convert current time to UTC
			for (int j = 0; j < (int)all_match_data.size(); j++)
			{
				if ((all_match_data[j]["id"].asString())[0] == region[0]) //filter down to matches in the region's range
				{
					for (int i = 0; i < (int)all_match_data[j]["maps"].size(); i++)
					{
						store_activityData(&all_match_data[j], i, con, ingame_clock_time, UTCTime);
						if (ingame_clock_time == 15)
						{
							//get ppt values
						}
						if (ingame_clock_time == 14)
						{ //only store mapscore data every point-tally in-game
							store_mapScores(&all_match_data[j], i, con, UTCTime);
						}
					}
				}
			}
		}
	}
	catch (exception &e)
	{
		force_resync = true;
		cout << e.what() << endl;
	}
}
void sync_to_ingame_clock(string region, double timeToSleep) //1 = NA, 2 = EU
{
	Easy request;
	stringstream matchDetails;
	Json::Value score_data;
	Json::Reader parser;
	int previousScore = 9999999, currentScore = 0; //initialize to very high value
	/* */
	string match_url = "https://api.guildwars2.com/v2/wvw/matches/" + region + "-1";
	//obtain the first match from the specified region. An entire region's matches
		//share the same in-game clock
	request.setOpt(Url(match_url));
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	if (timeToSleep < 0)
	{
		timeToSleep = 0;
	}
	usleep(timeToSleep); //wait some seconds to reduce the number of API calls made
	while (1)
	{
		try
		{
			request.perform();
			if (parser.parse(matchDetails.str(), score_data))
			{
				cout << "Syncing ..." << endl;
				if (stored_matchDetails && score_data["start_time"].asString() != previous_start_time)
				{
					cout << "NEW MATCH!" << endl;
					stored_matchDetails = false;
				}
				currentScore = score_data["scores"][FIRST_SRV].asInt() + score_data["scores"][SECOND_SRV].asInt() + score_data["scores"][THIRD_SRV].asInt();
				if (currentScore >= (previousScore+635))
				{
					break;
				}
				previousScore = currentScore;
			}
			else
			{
				cout << "Error parsing data in sync-loop!" << endl;
			}
			matchDetails.str("");
			matchDetails.clear();
		}
		catch (exception &e)
		{
			cout << e.what() << endl;
		}
		/* */
		usleep(MICROSEC*0.0833*TIME_RES); //sleep for 5 seconds
	}
	/* */
}
void collect_data(string region) //1 = North American, 2 = European
{
	try 
    {
    	sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
		driver = sql::mysql::get_mysql_driver_instance();
		con = driver->connect(IPADDR, USERNAME, PASSWORD);
		stmt = con->createStatement();
		stmt->execute("USE " DATABASE);
		delete stmt;
		//
		time_t beginTime, endTime;
		double elapsed_msecs;
		double ingame_clock_time = 14.0*60.0;
		sync_to_ingame_clock(region,0);
    	while (1)
		{
			beginTime = time(0);
			cout << "Beginning " << ingame_clock_time/60.0 << endl;
			get_matchDetails(region, con, ingame_clock_time/60.0);
			cout << "Ending " << ingame_clock_time/60.0 << endl;
			endTime = time(0);
			elapsed_msecs = difftime(endTime, beginTime) * MICROSEC;
			cout << elapsed_msecs/MICROSEC << " seconds elapsed" << endl;
			cout << "Time to sleep: " << (double)(MICROSEC*TIME_RES - elapsed_msecs)/MICROSEC << endl;
			if (elapsed_msecs/MICROSEC > TIME_RES || force_resync)
			{
				cout << "Too much time elapsed! Resyncing" << endl;
				elapsed_msecs = TIME_RES*MICROSEC-1;
				ingame_clock_time = 14*60.0;
				sync_to_ingame_clock(region,0);
				force_resync = false;
			}
			if (ingame_clock_time/TIME_RES == 15)
			{
				cout << "Sync-wait: " << (MICROSEC*0.75*TIME_RES - elapsed_msecs)/MICROSEC << endl;
				sync_to_ingame_clock(region,MICROSEC*0.60*TIME_RES - elapsed_msecs); //resync to in-game clock every cycle
				elapsed_msecs = MICROSEC*TIME_RES-1;
			}
			else if (ingame_clock_time/TIME_RES <= 1)
			{
				ingame_clock_time = 15*60.0 + TIME_RES; //15 minutes + TIME_RES because 1 TIME_RES is subtracted below
			}
			ingame_clock_time -= TIME_RES;
			usleep((double)(MICROSEC*TIME_RES - elapsed_msecs));
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
}
int main (int argc, char *argv[])
{
	collect_data("1");
	return 0;
}
